<?php

namespace Utopia\Preloader;

class Preloader
{
    /**
     * @var array
     */
    protected $ignores = [];

    /**
     * @var array
     */
    protected $paths = [];

    /**
     * @var int
     */
    protected $count = 0;

    /**
     * @var array
     */
    protected $included = [];

    /**
     * Constructor
     * 
     * @param string $paths
     */
    public function __construct(string ...$paths)
    {
        $this->paths = $paths;

        $require = __DIR__.'/../../../../composer/autoload_classmap.php';
        $classMap = (file_exists($require)) ? require $require : [];

        $this->paths = \array_merge(
            $this->paths,
            \array_values($classMap)
        );
    }
    
    /**
     * Paths
     * 
     * Path to load
     * 
     * @param string $paths
     * 
     * @return $this
     */
    public function paths(string ...$paths): self
    {
        $this->paths = \array_merge(
            $this->paths,
            $paths
        );

        return $this;
    }

    /**
     * Ignore
     * 
     * Ignore a given path or file
     * 
     * @param string $names
     * 
     * @return $this
     */
    public function ignore(string ...$names): self
    {
        foreach ($names as $name) {
            if (is_readable($name)) {
                $this->ignores[] = $name;
            } else {
                echo "[Preloader] Failed to ignore path `{$name}`".PHP_EOL;
            }
        }

        return $this;
    }

    /**
     * Load
     * 
     * Loads all preloader preconfigured paths and files
     */
    public function load(): void
    {
        foreach ($this->paths as $path) {
            $this->loadPath(\rtrim($path, '/'));
        }

        //echo "[Preloader] Preloaded {$already} files.".PHP_EOL;
    }

    /**
     * Get Count
     * 
     * Get the total number of loaded files.
     * 
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * Get List
     * 
     * Get a list of all included paths.
     * 
     * @return array
     */
    public function getList(): array
    {
        return $this->included;
    }

    /**
     * Load Path
     * 
     * Load a specific file or folder and nested folders.
     * 
     * @param string $path
     * @return void
     */
    private function loadPath(string $path): void
    {
        if (\is_dir($path)) {
            $this->loadDir($path);

            return;
        }

        $this->loadFile($path);
    }


    /**
     * Load Directory
     * 
     * Load a specific folder and nested folders.
     * 
     * @param string $path
     * @return void
     */
    private function loadDir(string $path): void
    {
        $handle = \opendir($path);

        while ($file = \readdir($handle)) {
            if (\in_array($file, ['.', '..'])) {
                continue;
            }

            $this->loadPath("{$path}/{$file}");
        }

        \closedir($handle);
    }

    /**
     * Load File
     * 
     * Load a specific file.
     * 
     * @param string $path
     * @return void
     */
    private function loadFile(string $path): void
    {
        if ($this->shouldIgnore($path)) {
            return;
        }
        
        if (\in_array(\realpath($path), $this->included)) {
            echo "[Preloader] Skiped `{$path}`".PHP_EOL;
            return;
        }
        
        try {
            // opcache_compile_file($path);
            require $path;
        } catch (\Throwable $th) {
            echo "[Preloader] Failed to load `{$path}`: ".$th->getMessage().PHP_EOL;
            return;
        }

        $this->included[] = $path;
        $this->count++;
    }

    /**
     * Should Ignore
     * 
     * Should a given path be ignored or not?
     * 
     * @param string $path
     * @return bool
     */
    private function shouldIgnore(?string $path): bool
    {
        if ($path === null) {
            return true;
        }

        if (!\in_array(\pathinfo($path, PATHINFO_EXTENSION), ['php'])) {
            return true;
        }

        foreach ($this->ignores as $ignore) {
            if (\strpos($path, $ignore) === 0) {
                return true;
            }
        }

        return false;
    }
}