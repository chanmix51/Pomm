<?php

class PgLookBoolType extends PgLookBaseType
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
