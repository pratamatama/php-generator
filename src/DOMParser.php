<?php

namespace App\Utilities;

use DOMDocument;
use DOMNode;

class DOMParser extends PreProcessor
{
    protected string $title = 'XLS Report Sample';

    protected int $headerRowsCount = 1;

    protected array $headers = [];

    protected array $refs = [];

    protected DOMDocument $dom;

    protected function loadDOM(string $fileName)
    {
        $html = view($fileName)->render();
        $this->dom = new DOMDocument();
        $this->dom->loadHTML($html);

        $title = $this->dom->getElementById('title')->textContent;
        $this->setTitle($title);
        $this->rename($this->title);
        return $this;
    }

    protected function getTrElements()
    {
        $thead = $this->dom->getElementsByTagName('thead')->item(0);
        return $thead?->getElementsByTagName('tr') ?? [];
    }

    protected function getAttributes(DOMNode $node)
    {
        return [
            'ref' => $node->getAttribute('ref'),
            'align' => $node->getAttribute('align'),
            'colspan' => (int) $node->getAttribute('colspan'),
            'rowspan' => (int) $node->getAttribute('rowspan'),
            'content' => $node->textContent,
        ];
    }

    protected function setRefs(string $key, DOMNode $element)
    {
        if (!isset($this->refs[$key])) {
            return $this->refs[$key] = [$element];
        }
        $this->refs[$key] = [...$this->refs[$key], $element];
    }

    protected function getParents(string $key)
    {
        return array_values(
            array_filter($this->headers, function ($item) use ($key) {
                $lcContent = \Str::lower($item['content']);
                $lcKey = \Str::lower($key);
                return $lcContent === $lcKey;
            })
        );
    }

    public function dump()
    {
        return dd($this);
    }

    public function setTitle(string $title)
    {
        if (!empty($title)) {
            $this->title = trim($title);
        }
        return $this;
    }

    public function fromBlade(string $fileName)
    {
        $this->loadDOM($fileName);

        $cell = 'A';

        $trs = $this->getTrElements();
        $this->headerRowsCount = $trs->count();

        for ($i = 0; $i < $this->headerRowsCount; $i++) {
            $row = $i + 1;
            $tr = $trs[$i];
            $ths = $tr->getElementsByTagName('th');

            for ($j = 0; $j < $ths->count(); $j++) {
                $position = $cell++ . $row;

                $th = $ths[$j];
                $attrs = $this->getAttributes($th);

                $header = [
                    'position' => $position,
                    ...$attrs,
                ];

                $hp = $header['position'];
                $letter = $hp[0];

                if ($attrs['rowspan'] !== 0) {
                    $mergeCell = $letter . $attrs['rowspan'];
                    $header['position'] = $this->adjustPosition($hp, $mergeCell);
                }

                if ($attrs['colspan'] !== 0) {
                    for ($k = 0; $k < $attrs['colspan'] - 1; $k++) {
                        $letter++;
                    }

                    $mergeCell = $letter . $row;
                    $header['position'] = $this->adjustPosition($hp, $mergeCell);
                }

                if (!empty($attrs['ref'])) {
                    $this->setRefs($attrs['ref'], $th);
                    $parents = $this->getParents($attrs['ref']);

                    if (!$parents || !$parents[0]) {
                        throw new Exception("You have an element with [ref] attribute [ref = {$attrs['ref']}] but it has no ref.");
                    }

                    $parentPosition = explode(':', $parents[0]['position']);
                    if (count($parentPosition) !== 2) {
                        throw new Exception("You have an element with [ref] attribute [ref = {$attrs['ref']}] which reference an element that has no [colspan].");
                    }

                    $refCellStart = $parentPosition[0][0];
                    for ($x = 0; $x < count($this->refs[$attrs['ref']]); $x++) {
                        $header['position'] = chr(ord($refCellStart) + $x)  . $row;
                    }
                }


                $this->headers = [...$this->headers, $header];

                for ($l = 0; $l < $attrs['colspan'] - 1; $l++) {
                    $cell++;
                }
            }

            $cell = 'A';
        }

        return $this;
    }
}
