package main

import (
    "bytes"
    "encoding/json"
    "fmt"
    "log"
    "os"
    "strings"
    "time"

    "github.com/google/uuid"
    "github.com/joho/godotenv"
    "github.com/redis/go-redis/v9"
    "github.com/valyala/fasthttp"
)

type Config struct {
    RedisURL      string
    RedisPassword string
    SecretToken   string
    ListenPort    string
    RedisPrefix   string
    HorizonPrefix string
}

func init() {
    envPath := ""
    if len(os.Args) > 1 {
        envPath = os.Args[1]
    }
    
    var err error
    if envPath != "" {
        err = godotenv.Load(envPath)
    } else {
        err = godotenv.Load()
    }
    
    if err != nil {
        log.Printf("Warning: Could not load .env file: %v", err)
        log.Println("Continuing with environment variables from system...")
    }
}

func serializePHPObject(botId, update string, timestamp int64) string {
    var buf bytes.Buffer

    className := "App\\Jobs\\ProcessTelegramUpdate"
    buf.WriteString(fmt.Sprintf("O:%d:\"%s\":3:{", len(className), className))

    buf.WriteString("s:6:\"bot_id\";")
    if botId == "" {
        buf.WriteString("N;")
    } else {
        buf.WriteString(fmt.Sprintf("s:%d:\"%s\";", len(botId), botId))
    }

    buf.WriteString("s:6:\"update\";")
    if update == "" {
        buf.WriteString("N;")
    } else {
        buf.WriteString(fmt.Sprintf("s:%d:\"%s\";", len(update), update))
    }

    buf.WriteString("s:9:\"timestamp\";")
    buf.WriteString(fmt.Sprintf("i:%d;", timestamp))

    buf.WriteString("}")
    return buf.String()
}

func main() {
    redisURL := os.Getenv("REDIS_URL")
    redisPassword := os.Getenv("REDIS_PASSWORD")
    secretToken := os.Getenv("SECRET_TOKEN")
    listenPort := os.Getenv("LISTEN_PORT")
    redisPrefix := os.Getenv("REDIS_PREFIX")
    horizonPrefix := os.Getenv("HORIZON_PREFIX")
    appName := os.Getenv("APP_NAME")

    if redisPrefix == "" {
        if appName == "" {
            redisPrefix = "laravel-database-"
        } else {
            redisPrefix = appName + "-database-"
        }
    }
    if horizonPrefix == "" {
        horizonPrefix = "laravel_horizon:"
    }

    cfg := Config{
        RedisURL:      redisURL,
        RedisPassword: redisPassword,
        SecretToken:   secretToken,
        ListenPort:    ":" + listenPort,
        RedisPrefix:   redisPrefix,
        HorizonPrefix: horizonPrefix,
    }

    opt, err := redis.ParseURL(cfg.RedisURL)
    if err != nil {
        log.Fatalf("Redis parse error: %v", err)
    }
    
    if cfg.RedisPassword != "" {
        opt.Password = cfg.RedisPassword
    }
    
    rdb := redis.NewClient(opt)

    server := &fasthttp.Server{
        Handler:            makeHandler(rdb, cfg.SecretToken, cfg.RedisPrefix, cfg.HorizonPrefix),
        ReadTimeout:        5 * time.Second,
        WriteTimeout:       5 * time.Second,
        MaxConnsPerIP:      100,
        MaxRequestsPerConn: 10000,
        Concurrency:        10000,
    }

    log.Print("Starting Telegram Proxy on ", cfg.ListenPort)
    if err := server.ListenAndServe(cfg.ListenPort); err != nil {
        panic(err)
    }
}

func makeHandler(rdb *redis.Client, secretToken, redisPrefix, horizonPrefix string) fasthttp.RequestHandler {
    return func(ctx *fasthttp.RequestCtx) {
        if !ctx.IsPost() || !strings.HasPrefix(string(ctx.Path()), "/telegram/bot/webhook/") {
            ctx.SetStatusCode(200)
            log.Print("Request doesn't contain /telegram/bot/webhook/")
            return
        }

        botId := strings.TrimPrefix(string(ctx.Path()), "/telegram/bot/webhook/")
        if botId == "" {
            ctx.SetStatusCode(200)
            log.Print("Bot id is empty")
            return
        }

        if secretToken != "" {
            providedToken := string(ctx.Request.Header.Peek("X-Telegram-Bot-Api-Secret-Token"))
            if providedToken != secretToken {
                ctx.SetStatusCode(200)
                log.Print("Invalid secretToken")
                return
            }
        }

        jobId := uuid.New().String()
        updateBody := string(ctx.PostBody())
        now := time.Now()
        nowUnix := now.Unix()
        micro := float64(now.UnixNano()) / 1e9
        microStr := fmt.Sprintf("%.4f", micro)

        job := map[string]interface{}{
            "uuid":          jobId,
            "displayName":   "App\\Jobs\\ProcessTelegramUpdate",
            "job":           "Illuminate\\Queue\\CallQueuedHandler@call",
            "maxTries":      nil,
            "pushedAt": microStr,
            "maxExceptions": nil,
            "failOnTimeout": false,
            "backoff":       nil,
            "timeout":       nil,
            "retryUntil":    nil,
            "data": map[string]interface{}{
                "command": serializePHPObject(botId, updateBody, nowUnix),
                "commandName": "App\\Jobs\\ProcessTelegramUpdate",
            },
            "id":        jobId,
            "attempts":  0,
            "silenced": false,
            "tags": []string{"telegram", "telegram-bot", "telegram-bot-update"},
        }

        jobJSON, err := json.Marshal(job)
        if err != nil {
            ctx.SetStatusCode(500)
            log.Printf("Job marshal error: %v", err)
            return
        }

        queueKey := fmt.Sprintf("%squeues:telegram-updates", redisPrefix)
        connection := "redis"
        queue := "telegram-updates"

        pipe := rdb.TxPipeline()

        // Horizon pending/recent
        pipe.ZAdd(ctx, horizonPrefix+"recent_jobs", redis.Z{Score: -micro, Member: jobId})
        pipe.ZAdd(ctx, horizonPrefix+"pending_jobs", redis.Z{Score: -micro, Member: jobId})
        pipe.HSet(ctx, horizonPrefix+jobId, map[string]interface{}{
            "id":         jobId,
            "connection": connection,
            "queue":      queue,
            "name":       "App\\Jobs\\ProcessTelegramUpdate",
            "status":     "pending",
            "payload":    jobJSON,
            "created_at": microStr,
            "updated_at": microStr,
        })
        pipe.ExpireAt(ctx, horizonPrefix+jobId, now.Add(60*time.Minute))

        pipe.RPush(ctx, queueKey, jobJSON)

        if _, err := pipe.Exec(ctx); err != nil {
            log.Printf("Redis pipeline error: %v", err)
        }

        ctx.SetStatusCode(200)
        log.Printf("Job %s pushed to queue %s", jobId, queueKey)
    }
}