<?php

namespace Pratamatama\PhpGenerator;

use Illuminate\Support\Collection;


class Processor
{
    protected Collection $data;

    protected Collection $modifiedData;

    protected string $fileName;

    protected array $renderKeys = [];

    protected bool $isAutoIncrement = true;

    public function setData(array | Collection $data)
    {
        $this->data = is_array($data) ? Collection::wrap($data) : $data;
        $this->modifiedData = $this->data;
        return $this;
    }

    public function rename(string $newName)
    {
        $this->fileName = $newName . '.xlsx';
        return $this;
    }

    public function modify(null|callable $modifier)
    {
        if ($this->data->count() === 1 && !is_null($modifier) && is_callable($modifier)) {
            $this->modifiedData = $modifier($this->modifiedData);
            return $this;
        }

        if (!is_null($modifier) && is_callable($modifier)) {
            $this->modifiedData = $this->modifiedData->map(function ($i) use ($modifier) {
                $modified = $modifier(Collection::wrap($i));
                return $modified;
            });
            return $this;
        }

        return $this;
    }

    public function render(array $keys)
    {
        $this->renderKeys = $keys;

        if (count($this->renderKeys) !== 0) {
            if ($this->modifiedData->count() === 1) {
                $this->modifiedData = $this->modifiedData->only($this->renderKeys);
            } else {
                $this->modifiedData = $this->modifiedData->map(function ($d) {
                    $item = Collection::wrap($d)->only($this->renderKeys);
                    return $item;
                });
            }
        }

        return $this;
    }

    public function autoIncrement(bool $value = true)
    {
        $this->isAutoIncrement = $value;
        return $this;
    }
}
