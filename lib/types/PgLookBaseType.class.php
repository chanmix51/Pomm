<?php

abstract class PgLookBaseType
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
