<?php

namespace Pomm\Exception;

/**
 * Pomm\SqlException - errors from the rdbms with the result resource.
 *
 * @link http://www.postgresql.org/docs/8.4/static/errcodes-appendix.html
 * @package Pomm
 * @uses Pomm\Exception
 * @version $id$
 * @copyright 2011 - 2013 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class SqlException extends Exception
{
    protected $result_resource;

    /**
     * __construct
     *
     * @param Resource     $result_resource
     * @param Mixed        $sql
     */
    public function __construct($result_resource, $sql)
    {
        $this->result_resource = $result_resource;
        $this->message = sprintf("«%s».\n\nSQL error state '%s' [%s]\n====\n%s\n====", $sql, $this->getSQLErrorState(), $this->getSQLErrorSeverity(), $this->getSqlErrorMessage());
    }

    /**
     * getSQLErrorState
     *
     * Returns the SQLSTATE of the last SQL error.
     *
     * @link http://www.postgresql.org/docs/9.0/interactive/errcodes-appendix.html
     * @return String
     */
    public function getSQLErrorState()
    {
        return pg_result_error_field($this->result_resource, \PGSQL_DIAG_SQLSTATE);
    }

    /**
     * getSQLErrorSeverity
     *
     * Returns the severity level of the error.
     *
     * @return String
     */
    public function getSQLErrorSeverity()
    {
        return pg_result_error_field($this->result_resource, \PGSQL_DIAG_SEVERITY);
    }

    /**
     * getSqlErrorMessage
     *
     * Returns the error message sent by the server.
     *
     * @return String
     */

    public function getSqlErrorMessage()
    {
        return pg_result_error($this->result_resource);
    }

    /**
     * getSQLDetailedErrorMessage
     *
     * @return String
     */
    public function getSQLDetailedErrorMessage()
    {
        return sprintf("«%s»\n%s\n(%s)", pg_result_error_field($this->result_resource, \PGSQL_DIAG_MESSAGE_PRIMARY), pg_result_error_field($this->result_resource, \PGSQL_DIAG_MESSAGE_DETAIL), pg_result_error_field($this->result_resource, \PGSQL_DIAG_MESSAGE_HINT));
    }
}
