<?php

namespace App\Core\Mvc\View\Helper;

class Layout
{
    private ?string $layout = null;

    private array $blocks = [];
    private ?string $currentBlock = null;

    public function __invoke()
    {
        return $this;
    }

    public function hasLayout(): bool
    {
        return !empty($this->layout);
    }

    public function getLayout(): ?string
    {
        return $this->layout;
    }

    public function extend(string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    public function startBlock(string $name): self
    {
        if ($this->currentBlock !== null) {
            throw new \LogicException('A layout block is already open.');
        }

        $this->currentBlock = $name;
        ob_start();

        return $this;
    }

    public function endBlock(?string $name = null): self
    {
        if ($this->currentBlock === null) {
            throw new \LogicException('No layout block is currently open.');
        }
        if ($name !== null && $name !== $this->currentBlock) {
            throw new \LogicException('The layout block name does not match.');
        }

        $this->blocks[$this->currentBlock] = ob_get_clean();
        $this->currentBlock = null;

        return $this;
    }

    public function block(string $name, string $default = ''): string
    {
        return $this->blocks[$name] ?? $default;
    }

    public function reset(): void
    {
        $this->layout = null;
        $this->blocks = [];
        $this->currentBlock = null;
    }
}