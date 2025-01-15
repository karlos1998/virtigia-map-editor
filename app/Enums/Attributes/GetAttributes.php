<?php

namespace App\Enums\Attributes;

use Illuminate\Support\Str;
use ReflectionClassConstant;

trait GetAttributes
{
    public function description(): string
    {
        $ref = new ReflectionClassConstant(self::class, $this->name);
        $classAttributes = $ref->getAttributes(Description::class);

        if (count($classAttributes) === 0) {
            return Str::headline($this->value);
        }

        return $classAttributes[0]->newInstance()->description;
    }
//
//    public function shortDescription(): string
//    {
//        $ref = new ReflectionClassConstant(self::class, $this->name);
//        $classAttributes = $ref->getAttributes(ShortDescription::class);
//
//        if (count($classAttributes) === 0) {
//            return Str::headline($this->value);
//        }
//
//        return $classAttributes[0]->newInstance()->description;
//    }
//
//    public function genitiveDescription(): string
//    {
//        $ref = new ReflectionClassConstant(self::class, $this->name);
//        $classAttributes = $ref->getAttributes(GenitiveDescription::class);
//
//        if (count($classAttributes) === 0) {
//            return Str::headline($this->value);
//        }
//
//        return $classAttributes[0]->newInstance()->description;
//    }
}
