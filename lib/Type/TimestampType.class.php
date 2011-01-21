<?php
namespace Pomm\Type;

class TimestampType extends BaseType
{
  public static function fromPg($data)
  {
    return new \DateTime($data);
  }

  public static function toPg($data)
  {
    if (!$data instanceof \DateTime)
    {
      $data = new \DateTime($data);
    }

    return sprintf("'%s'", $data->format('Y-m-d H:i:s.u'));
  }
}
