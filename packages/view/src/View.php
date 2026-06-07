<?php

declare(strict_types=1);

namespace Codemonster\View;

use Codemonster\View\Contracts\SupportsInspectionInterface;
use Codemonster\View\Locator\LocatorInterface;

class View
{
    /** @var array<string, EngineInterface> */
    protected array $engines;

    protected string $default;

    /** @param array<string, EngineInterface> $engines */
    public function __construct(array $engines = [], string $default = 'php')
    {
        $this->engines = $engines;
        $this->default = $default;
    }

    public function addEngine(string $name, EngineInterface $engine): void
    {
        $this->engines[$name] = $engine;
    }

    /** @param array<string, mixed> $data */
    public function render(string $view, array $data = [], ?string $engine = null): string
    {
        if ($engine !== null) {
            return $this->renderWith($engine, $view, $data);
        }

        $detected = $this->detectEngineByExtension($view);

        if ($detected !== null) {
            return $this->renderWith($detected, $view, $data);
        }

        return $this->renderWith($this->default, $view, $data);
    }

    protected function detectEngineByExtension(string $view): ?string
    {
        foreach ($this->engines as $name => $engine) {
            if (!$engine instanceof SupportsInspectionInterface) {
                continue;
            }

            $locator = $engine->getLocator();

            foreach ($engine->getExtensions() as $ext) {
                $path = $this->tryResolve($locator, $view, $ext);

                if ($path !== null) {
                    return $name;
                }
            }
        }

        return null;
    }

    protected function tryResolve(LocatorInterface $locator, string $view, string $extension): ?string
    {
        try {
            $path = $locator->resolve($view, $extension);

            return file_exists($path) ? $path : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @param array<string, mixed> $data */
    protected function renderWith(string $engine, string $view, array $data): string
    {
        if (!isset($this->engines[$engine])) {
            throw new \RuntimeException("View engine [{$engine}] not registered.");
        }

        return $this->engines[$engine]->render($view, $data);
    }

    public function getLocator(): ?LocatorInterface
    {
        $engine = $this->engines[$this->default] ?? null;

        if ($engine instanceof SupportsInspectionInterface) {
            return $engine->getLocator();
        }

        return null;
    }

    public function addNamespace(string $namespace, string $path): void
    {
        $engine = $this->engines[$this->default] ?? null;

        if (!$engine instanceof SupportsInspectionInterface) {
            throw new \RuntimeException('Default engine does not support locators.');
        }

        $locator = $engine->getLocator();

        if (method_exists($locator, 'addPath')) {
            $locator->addPath($path, $namespace);
        } else {
            throw new \RuntimeException('Locator does not support addPath method.');
        }
    }
}
