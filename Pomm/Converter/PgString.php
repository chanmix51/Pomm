<?php
namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;

class PgString implements ConverterInterface
{
  public function toPg($data)
  {
    $data = str_replace("'", "''", $data);

    return sprintf("'%s'", $data);
  }

  public function fromPg($data)
  {
    return trim($data, '"');
  }
}
