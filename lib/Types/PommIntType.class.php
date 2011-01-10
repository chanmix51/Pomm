<?php
Namespace Pomm;

class PommIntType extends PommBaseType
{
  public static function fromPg($data)
  {
    return $data;
  }

  public static function toPg($data)
  {
    return $data;
  }
}
