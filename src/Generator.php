<?php

namespace Pratamatama\PhpGenerator;

class Generator
{
    public static function xlsx(): XLSGenerator
    {
        return (new XLSGenerator);
    }
}
