<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing;

use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use HybridGram\Core\Routing\RouteOptions\DocumentOptions;
use HybridGram\Core\Routing\RouteOptions\PollOptions;
use HybridGram\Telegram\Document\MimeType;

final class RouteGroup
{
    private array $attributes = [] {
        get {
            return $this->attributes;
        }
    }

    public function __construct(array $attributes = [])
    {
        if (! isset($attributes['for_bot'])) {
            throw new InvalidArgumentException('for_bot parameter is required.');
        }

        if (isset($attributes['from_state'])) {
            if (! is_array($attributes['from_state'])) {
                throw new InvalidArgumentException('from_state parameter must be array of strings.');
            }

            foreach ($attributes['from_state'] as $state) {
                if (! is_string($state)) {
                    throw new InvalidArgumentException('from_state array must contain only strings.');
                }
            }
        }

        if (isset($attributes['to_state'])) {
            if (! is_string($attributes['to_state']) && $attributes['to_state'] !== null) {
                throw new InvalidArgumentException('to_state parameter must be string, null, or array of strings.');
            }
        }

        if (isset($attributes['chat_type'])) {
            $chatType = $attributes['chat_type'];
            if (!($chatType instanceof ChatType)
                && !is_array($chatType)) {
                throw new InvalidArgumentException('chat_type should be instance of '.ChatType::class.', array of '.ChatType::class.', or null');
            }
            if (is_array($chatType)) {
                foreach ($chatType as $type) {
                    if (!($type instanceof ChatType)) {
                        throw new InvalidArgumentException('chat_type array must contain only instances of '.ChatType::class);
                    }
                }
            }
        }

        if (isset($attributes['cache_key']) && ! is_string($attributes['cache_key'])) {
            throw new InvalidArgumentException('cache_key parameter must be string or array of strings.');
        }

        if (isset($attributes['cache_ttl']) && ! is_int($attributes['cache_ttl'])) {
            throw new InvalidArgumentException('cache_ttl parameter must be integer.');
        }

        if (isset($attributes['middlewares'])) {
            if (! is_array($attributes['middlewares'])) {
                throw new InvalidArgumentException('middlewares parameter must be array.');
            }

            foreach ($attributes['middlewares'] as $middleware) {
                if (! is_string($middleware) && ! is_object($middleware)) {
                    throw new InvalidArgumentException('middlewares array must contain only strings or objects.');
                }
            }
        }

        if (isset($attributes['send_action']) && ! ($attributes['send_action'] instanceof ActionType)) {
            throw new InvalidArgumentException('action_type should be instance of '.ActionType::class);
        }
        $this->attributes = $attributes;
    }

    public function addAttributesToBuilder(TelegramRouteBuilder $builder): TelegramRouteBuilder
    {
        if (isset($this->attributes['for_bot'])) {
            $builder->forBot($this->attributes['for_bot']);
        }

        if (isset($this->attributes['from_state'])) {
            $builder->fromUserState($this->attributes['from_state']);
        }

        if (isset($this->attributes['to_state'])) {
            $builder->toUserState($this->attributes['to_state']); // todo разобраться со вторым вариантом метода для чатов
        }

        if (isset($this->attributes['chat_type'])) {
            $chatType = $this->attributes['chat_type'];
            if ($chatType === null) {
                $builder->chatTypes(null);
            } elseif (is_array($chatType)) {
                $builder->chatTypes($chatType);
            } else {
                $builder->chatType($chatType);
            }
        }

        if (isset($this->attributes['cache_key'])) {
            $builder->cache($this->attributes['cache_ttl'], $this->attributes['cache_key']);
        }

        if (isset($this->attributes['middlewares'])) {
            $builder->middlewares($this->attributes['middlewares']);
        }

        if (isset($this->attributes['send_action'])) {
            $builder->sendAction($this->attributes['send_action']);
        }

        $builder->setResetCallback(function (TelegramRouteBuilder $builder) {
            $this->addAttributesToBuilder($builder);
        });

        return $builder;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }
}
