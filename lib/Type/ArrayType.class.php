<?php
namespace Pomm\Type;

class ArrayType extends BaseType
{
  protected static $subtype;

  public static function setSubType($type)
  {
    self::$subtype = $type;
  }

  public static function fromPg($data)
  {
    $data = ltrim(rtrim($data, '}'), '{');
    $data_array = preg_split('/,/', $data);

    $out_data = array();
    $type = self::$subtype;
    foreach ($data_array as $data)
    {
      $out_data[] = call_user_func(array($type, 'fromPg'), $data);
    }

    return $out_data;
  }

  public static function toPg($data)
  {
    return sprintf("'{%s}'", join(',', $data));
  }
}
