<?php
namespace Pomm\Type;


class BoolType extends BaseType
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
