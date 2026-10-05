<?php

namespace App\Core\Command\Output;

class Table
{
    private $headers = [];
    private $rows = [];
    private $columnWidths = [];

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
        
    }

    public function addRow(array $row): void
    {
        $this->rows[] = $row;
    }

    public function render(): void
    {
        $this->calculateWidths();

        $border = $this->renderBorder();
        echo $border . PHP_EOL;

        if (!empty($this->headers)) {
            echo $this->renderRow($this->headers, true) . PHP_EOL;
            echo $border . PHP_EOL;
        }
        
        foreach ($this->rows as $row) {
            echo $this->renderRow($row) . PHP_EOL;
            echo $border . PHP_EOL;
        }
        echo $border . PHP_EOL;
    }

    private function calculateWidths(): void
    {
        $this->columnWidths = [];
        foreach ($this->headers as $i => $header) {
            $this->columnWidths[$i] = mb_strwidth((string)$header);
        }
        foreach ($this->rows as $row) {
            foreach ($row as $i => $cell) {
                $this->columnWidths[$i] = max($this->columnWidths[$i] ?? 0, mb_strwidth((string)$cell));
            }
        }
    }

    private function renderBorder(): string
    {
        $parts = '';
        foreach ($this->columnWidths as $width) {
        // +2 pour inclure les espaces autour du contenu de la cellule
        $parts[] = str_repeat('=', $width + 2);
        }
        return '+' . implode('+', $parts) . '+';
    }

    private function renderRow(array $row, bool $isHeader = false): string
    {
        $cells = [];
        foreach (array_values($row) as $i => $value) {
            $width = $this->columnWidths[$i];
            $strValue = (string)$value;
            $paddingLength = max(0, $width - mb_strwidth($strValue));
            $paddedCell = $strValue . str_repeat(' ', $paddingLength);
            if ($isHeader) {
                $paddedCell = Color::apply($paddedCell, 'cyan');
            }
            $cells[] = ' ' . $paddedCell . ' ';
        }
        return '| ' . implode(' | ', $cells) . ' |';
    }
}