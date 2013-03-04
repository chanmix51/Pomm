<?php
namespace Pomm\Exception;

/**
 * Pomm\SqlException - errors from the rdbms with the PDOStatement object
 *
 * @link http://www.postgresql.org/docs/8.4/static/errcodes-appendix.html
 * @package Pomm
 * @uses Pomm\Exception
 * @version $id$
 * @copyright 2011 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */

class SqlException extends Exception
{
  protected $error_state;

  /**
   * __construct
   *
   * @param PDOStatement $stmt
   * @param Mixed        $sql
   */
  public function __construct(\PDOStatement $stmt, $sql)
  {
    $this->error_state = $stmt->errorInfo();
    $this->message = sprintf("«%s».\n\nSQL error state '%s'\nextended status '%s'\n====\n%s\n====", $sql, $this->error_state[0], $this->error_state[1], $this->error_state[2]);
  }

  /**
   * getSQLErrorState
   *
   * Returns the SQLSTATE of the last SQL error.
   *
   * @link http://www.postgresql.org/docs/8.4/interactive/errcodes-appendix.html
   * @return String
   */
  public function getSQLErrorState()
  {
    return $this->error_state[0];
  }

  /**
   * getSQLExtendedErrorStatus
   *
   * Returns the internal driver error code.
   *
   * @return String
   */
  public function getSQLExtendedErrorStatus()
  {
    return $this->error_state[1];
  }

  /**
   * getSQLErrorMessage
   *
   * @return String
   */
  public function getSQLErrorMessage()
  {
    return $this->error_state[2];
  }
}
