<?php

declare(strict_types=1);

namespace HybridGram\Core\Routing\Attributes;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use SplFileInfo;

final class AttributeRouteScanner
{
    /**
     * @param  list<string>  $directories
     * @param  list<string>  $excludeDirectories
     */
    public function __construct(
        private readonly array $directories,
        private readonly array $excludeDirectories = [],
    ) {}

    /**
     * @return list<DiscoveredRoute>
     */
    public function scan(): array
    {
        // Try to use Composer's optimized classmap first (faster)
        $classes = $this->getClassesFromComposer();

        if (! empty($classes)) {
            if ($routes = $this->scanClasses($classes)) {
                return $routes;
            }
        }

        // Fallback to filesystem scanning (slower but always works)
        return $this->scanFilesystem();
    }

    /**
     * Scan using Composer's classmap (fast path).
     *
     * @param  list<string>  $classes
     * @return list<DiscoveredRoute>
     */
    private function scanClasses(array $classes): array
    {
        $discoveredRoutes = [];

        foreach ($classes as $fqcn) {
            $routes = $this->processClass($fqcn);
            array_push($discoveredRoutes, ...$routes);
        }

        return $discoveredRoutes;
    }

    /**
     * Scan using filesystem iteration (slow but reliable fallback).
     *
     * @return list<DiscoveredRoute>
     */
    private function scanFilesystem(): array
    {
        $discoveredRoutes = [];

        foreach ($this->findPhpFiles() as $filePath) {
            $fqcn = $this->extractFqcn($filePath);
            if ($fqcn === null) {
                continue;
            }

            $routes = $this->processClass($fqcn);
            array_push($discoveredRoutes, ...$routes);
        }

        return $discoveredRoutes;
    }

    /**
     * Process a single class and extract route attributes.
     *
     * @return list<DiscoveredRoute>
     */
    private function processClass(string $fqcn): array
    {
        if (! class_exists($fqcn, true)) {
            return [];
        }

        $reflection = new ReflectionClass($fqcn);

        if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait() || $reflection->isEnum()) {
            return [];
        }

        $classConfigAttributes = $this->getConfigAttributes($reflection);
        $discoveredRoutes = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor() || $method->isDestructor() || $method->isStatic()) {
                continue;
            }

            if ($method->getDeclaringClass()->getName() !== $fqcn) {
                continue;
            }

            $routeAttributes = $this->getRouteAttributes($method);
            if (empty($routeAttributes)) {
                continue;
            }

            $methodConfigAttributes = $this->getConfigAttributes($method);

            foreach ($routeAttributes as $routeAttribute) {
                $discoveredRoutes[] = new DiscoveredRoute(
                    className: $fqcn,
                    methodName: $method->getName(),
                    routeAttribute: $routeAttribute,
                    classConfigAttributes: $classConfigAttributes,
                    methodConfigAttributes: $methodConfigAttributes,
                );
            }
        }

        return $discoveredRoutes;
    }

    /**
     * Get classes from Composer's optimized classmap.
     *
     * @return list<string>
     */
    private function getClassesFromComposer(): array
    {
        $classmapPath = base_path('vendor/composer/autoload_classmap.php');

        if (! file_exists($classmapPath)) {
            return [];
        }

        /** @var mixed $classmap */
        $classmap = require $classmapPath;

        if (! is_array($classmap) || empty($classmap)) {
            return [];
        }

        return $this->filterClassesByDirectories($classmap);
    }

    /**
     * Filter classes to only include those in target directories.
     *
     * @param  array<mixed, mixed>  $classmap  FQCN => file path
     * @return list<string> Filtered FQCNs
     */
    private function filterClassesByDirectories(array $classmap): array
    {
        $normalizedDirs = array_map(
            fn (string $dir) => realpath($dir) ?: $dir,
            $this->directories,
        );

        $normalizedExcludes = array_map(
            fn (string $dir) => realpath($dir) ?: $dir,
            $this->excludeDirectories,
        );

        $filteredClasses = [];

        foreach ($classmap as $fqcn => $filePath) {
            if (! is_string($fqcn) || ! is_string($filePath)) {
                continue;
            }

            $realPath = realpath($filePath);
            if ($realPath === false) {
                continue;
            }

            // Check if file is in one of target directories
            $inTargetDir = false;
            foreach ($normalizedDirs as $dir) {
                if (str_starts_with($realPath, $dir)) {
                    $inTargetDir = true;
                    break;
                }
            }

            if (! $inTargetDir) {
                continue;
            }

            // Check if file is not in excluded directories
            if ($this->isExcluded($realPath, $normalizedExcludes)) {
                continue;
            }

            $filteredClasses[] = $fqcn;
        }

        return $filteredClasses;
    }

    /**
     * @return \Generator<string>
     */
    private function findPhpFiles(): \Generator
    {
        $normalizedExcludes = array_map(
            fn (string $dir) => realpath($dir) ?: $dir,
            $this->excludeDirectories,
        );

        foreach ($this->directories as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $realPath = $file->getRealPath();
                if ($realPath === false) {
                    continue;
                }

                if ($this->isExcluded($realPath, $normalizedExcludes)) {
                    continue;
                }

                yield $realPath;
            }
        }
    }

    /**
     * @param  list<string>  $excludes
     */
    private function isExcluded(string $filePath, array $excludes): bool
    {
        foreach ($excludes as $exclude) {
            if (str_starts_with($filePath, $exclude)) {
                return true;
            }
        }

        return false;
    }

    private function extractFqcn(string $filePath): ?string
    {
        $contents = file_get_contents($filePath);
        if ($contents === false) {
            return null;
        }

        $tokens = token_get_all($contents);
        $namespace = '';
        $class = '';

        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            if (! is_array($tokens[$i])) {
                continue;
            }

            if ($tokens[$i][0] === T_NAMESPACE) {
                $namespace = $this->extractAfterToken($tokens, $i, $count);
            }

            if ($tokens[$i][0] === T_CLASS) {
                // Skip "::class" usage
                if ($i > 0) {
                    $prev = $i - 1;
                    while ($prev >= 0 && is_array($tokens[$prev]) && $tokens[$prev][0] === T_WHITESPACE) {
                        $prev--;
                    }
                    if ($prev >= 0 && is_array($tokens[$prev]) && $tokens[$prev][0] === T_DOUBLE_COLON) {
                        continue;
                    }
                }

                // Get class name from next non-whitespace token
                for ($j = $i + 1; $j < $count; $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                        continue;
                    }
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        $class = $tokens[$j][1];
                    }
                    break;
                }
                break;
            }
        }

        if ($class === '') {
            return null;
        }

        return $namespace !== '' ? $namespace.'\\'.$class : $class;
    }

    /**
     * @param  list<string|array{0: int, 1: string, 2: int}>  $tokens
     */
    private function extractAfterToken(array $tokens, int $index, int $count): string
    {
        $result = '';
        for ($i = $index + 1; $i < $count; $i++) {
            if (is_array($tokens[$i])) {
                if ($tokens[$i][0] === T_WHITESPACE) {
                    continue;
                }
                if ($tokens[$i][0] === T_STRING || $tokens[$i][0] === T_NAME_QUALIFIED) {
                    $result .= $tokens[$i][1];
                } else {
                    break;
                }
            } elseif ($tokens[$i] === ';') {
                break;
            } else {
                break;
            }
        }

        return $result;
    }

    /**
     * @return list<TelegramRouteAttribute>
     */
    private function getRouteAttributes(ReflectionMethod $method): array
    {
        $attributes = [];
        foreach ($method->getAttributes() as $attr) {
            if (is_subclass_of($attr->getName(), TelegramRouteAttribute::class)) {
                $instance = $attr->newInstance();
                if ($instance instanceof TelegramRouteAttribute) {
                    $attributes[] = $instance;
                }
            }
        }

        return $attributes;
    }

    /**
     * @param  ReflectionClass<object>|ReflectionMethod  $target
     * @return list<TelegramRouteConfigAttribute>
     */
    private function getConfigAttributes(ReflectionClass|ReflectionMethod $target): array
    {
        $attributes = [];
        foreach ($target->getAttributes() as $attr) {
            if (is_subclass_of($attr->getName(), TelegramRouteConfigAttribute::class)) {
                $instance = $attr->newInstance();
                if ($instance instanceof TelegramRouteConfigAttribute) {
                    $attributes[] = $instance;
                }
            }
        }

        return $attributes;
    }
}
