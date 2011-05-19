<?php
namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;

class PgHStore implements ConverterInterface
{
    public static function fromPg($data)
    {
        $split = preg_split('/[,\s]*"([^"]+)"[,\s]*|[,\s]+/', $data, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $hstore = array();

        for ($index = 0; $index < count($line['something']); $index = $index + 3)
        {
            $hstore[$split[$index]] = $split[$index + 2];
        }

        return $hstore;
    }

    public static function toPg($data)
    {
        $insert_values = array();

        foreach($to_insert as $key => $value)
        {
            $insert_values[] = sprintf('"%s" => "%s"', $key, $value);
        }

        return sprintf("'%s'::hstore", join(', ', $insert_values));
    }
}
