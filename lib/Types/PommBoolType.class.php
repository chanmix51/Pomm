<?php
Namespace Pomm;


class PommBoolType extends PommBaseType
{
  public static function fromPg($data)
  {
    return ($data == 't');
  }

  public static function toPg($data)
  {
    return $data ? "'true'" : "'false'";
  }
}
