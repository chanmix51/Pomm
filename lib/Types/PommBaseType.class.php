<?php
Namespace Pomm;

abstract class PommBaseType
{
  public static function toPg($data)
  {
    return $data;
  }

  public static function fromPg($data)
  {
    return $data;
  }
}
