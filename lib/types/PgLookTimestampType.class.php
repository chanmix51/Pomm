<?php

class PgLookTimestampType extends PgLookBaseType
{
  public static function fromPg($data)
  {
    return new DateTime($data);
  }

  public static function toPg($data)
  {
    if (!$data instanceof DateTime)
    {
      $data = new DateTime($data);
    }

    return sprintf("'%s'", $data->format(sfConfig::get('app_timestamp_format', 'Y-m-d H:i:s.u')));
  }
}
