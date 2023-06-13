<?php

namespace App\Utilities;

class Generator
{
    public static function xlsx(): XLSGenerator
    {
        return (new XLSGenerator);
    }
}
