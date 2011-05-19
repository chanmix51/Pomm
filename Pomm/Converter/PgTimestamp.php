<?php
namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;

class PgTimestamp implements ConverterInterface
{
  public function fromPg($data)
  {
    return new \DateTime($data);
  }

  public function toPg($data)
  {
    if (!$data instanceof \DateTime)
    {
      $data = new \DateTime($data);
    }

    return sprintf("'%s'", $data->format('Y-m-d H:i:s.u'));
  }
}
