<?php

class PgLookIntType extends PgLookBaseType
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
