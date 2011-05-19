<?php
namespace Pomm\Converter;

use Pomm\Converter\ConverterInterface;
use Pomm\Exception\Exception as PommException;

class PgHStore implements ConverterInterface
{
    public function fromPg($data)
    {
        $split = preg_split('/[,\s]*"([^"]+)"[,\s]*|[,\s]+/', $data, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $hstore = array();

        for ($index = 0; $index < count($line['something']); $index = $index + 3)
        {
            $hstore[$split[$index]] = $split[$index + 2];
        }

        return $hstore;
    }

    public function toPg($data)
    {
        if (!is_array($data))
        {
            throw new PommException(sprintf("HStore::toPg takes an associative array as parameter ('%s' given).", gettype($data)));
        }

        $insert_values = array();

        foreach($to_insert as $key => $value)
        {
            $insert_values[] = sprintf('"%s" => "%s"', $key, $value);
        }

        return sprintf("'%s'::hstore", join(', ', $insert_values));
    }
}
