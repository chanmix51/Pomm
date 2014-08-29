<?php

namespace Pomm\Connection;

use Pomm\Exception\ConnectionException;
use Pomm\Exception\SqlException;
use Psr\Log\LogLevel;

/**
 * Pomm\Connection\Service
 *
 * Manage a service layer to handle business transactions and observers.
 *
 * @package Pomm
 * @version $id$
 * @copyright 2011 - 2014 Grégoire HUBERT
 * @author Grégoire HUBERT <hubert.greg@gmail.com>
 * @license X11 {@link http://opensource.org/licenses/mit-license.php}
 */
class Service
{
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * begin
     *
     * Start a new transaction.
     *
     * @access public
     * @return Service $this
     */
    protected function begin()
    {
        try
        {
            $this->connection->executeAnonymousQuery(sprintf("BEGIN TRANSACTION ISOLATION LEVEL %s", $this->connection->isolation));
        }
        catch(ConnectionException $e)
        {
            $this->connection->connection->log(LogLevel::ERROR, sprintf("Cannot begin transaction (isolation level '%s').", $this->connection->isolation));

            throw $e;
        }

        return $this;
    }

    /**
     * commit
     *
     * Commit a transaction in the database.
     *
     * @access public
     * @return Service $this
     */
    protected function commit()
    {
        try
        {
            $this->connection->executeAnonymousQuery("COMMIT TRANSACTION");
        }
        catch(ConnectionException $e)
        {
            $this->connection->log(LogLevel::ERROR, sprintf("Cannot commit transaction (isolation level '%s').", $this->connection->isolation));

            throw $e;
        }

        return $this;
    }

    /**
     * rollback
     *
     * Rollback a transaction. This can be the whole transaction
     * or, if a savepoint name is specified, only the queries since
     * this savepoint.
     *
     * @access public
     * @param  String       $name Optional name of the savepoint.
     * @return Service $this
     */
    protected function rollback($name = null)
    {
        try
        {
            if (is_null($name))
            {
                $this->connection->executeAnonymousQuery('ROLLBACK TRANSACTION');
            }
            else
            {
                $this->connection->executeAnonymousQuery(sprintf("ROLLBACK TO SAVEPOINT %s", $this->connection->escapeIdentifier($name)));
            }
        }
        catch (ConnectionException $e)
        {
            $this->connection->log(LogLevel::ERROR, sprintf("Failed to rollback transaction (savepoint '%s') with isolation transaction '%s'.", $name, $this->connection->isolation));

            throw $e;
        }

        return $this;
    }

    /**
     * setSavepoint
     *
     * Set a new savepoint with the given name.
     *
     * @access public
     * @param  String       $name Savepoint's name.
     * @return Service $this
     */
    protected function setSavepoint($name)
    {
        try
        {
            $this->connection->executeAnonymousQuery(sprintf("SAVEPOINT %s", $this->connection->escapeIdentifier($name)));
        }
        catch (ConnectionException $e)
        {
            $this->connection->log(LogLevel::ERROR, sprintf("Failed to create savepoint '%s'.", $name));

            throw $e;
        }

        return $this;
    }

    /**
     * releaseSavepoint
     *
     * Forget the specified savepoint.
     *
     * @access public
     * @param  String       $name the savepoint's name.
     * @return Service $this
     */
    protected function releaseSavepoint($name)
    {
        try
        {
            $this->connection->executeAnonymousQuery(sprintf("RELEASE SAVEPOINT %s", $this->connection->escapeIdentifier($name)));
        }
        catch (ConnectionException $e)
        {
            $this->connection->log(LogLevel::ERROR, sprintf("Failed to release savepoint '%s'.", $name));

            throw $e;
        }

        return $this;
    }

    /**
     * setConstraints
     *
     * Set constraints deferred or immediate in the current transaction.
     * Since indicating the schema is recommended, Postgresql will look through
     * the search path if none is specified in the keys. If the schema name is
     * specified, it is prepend to the keys names.
     *
     * @access public
     * @param Array contraint names (['ALL'] for all constraints)
     * @param String the schema name
     * @param String CONSTRAINTS_IMMEDIATE or CONSTRAINTS_DEFERRED
     * @return Service
     */
    protected function setConstraints(Array $names, $schema = null, $check = null)
    {
        if ($check === null)
        {
            $check = static::CONSTRAINTS_DEFERRED;
        }

        if ($schema !== null)
        {
            foreach($names as $index => $name)
            {
                $names[$index] = sprintf("%s.%s", $schema, $name);
            }
        }

        if (!$this->isInTransaction())
        {
            throw new ConnectionException(sprintf("Cannot set constraints timing while not in a transaction (%s {%s}).", join(', ', $names), $check));
        }

        try
        {
            $this->connection->executeAnonymousQuery(sprintf("SET CONSTRAINTS %s %s", join(', ', $names), $check));
        }
        catch(ConnectionException $e)
        {
            $this->connection->log(LogLevel::ERROR, sprintf("Failed to set constraints {%s} to '%s'.", join(', ', $names), $check));

            throw $e;
        }

        return $this;
    }

    /**
     * isInTransaction
     *
     * Check if we are in transaction mode.
     *
     * @access public
     * @return boolean
     */
    protected function isInTransaction()
    {
        $status = $this->getTransactionStatus();

        return (bool) ($status === \PGSQL_TRANSACTION_INTRANS || $status === \PGSQL_TRANSACTION_INERROR || $status === \PGSQL_TRANSACTION_ACTIVE);
    }

    /**
     * isTransactionValid
     *
     * Is the current transaction is a valid state ?
     *
     * @return Bool
     */

    protected function isTransactionValid()
    {
        $status = $this->getTransactionStatus();

        return (bool) ($status === \PGSQL_TRANSACTION_INTRANS || $status === \PGSQL_TRANSACTION_ACTIVE);
    }

    /**
     * getTransactionStatus
     *
     * Return the transaction status of the connection. Because PHP's
     * documentation is not clear about what constant is what and begging to
     * know what integer 2 is, here is an array of constants values:
     * PGSQL_TRANSACTION_IDLE    = 0 ; ready, not in transaction
     * PGSQL_TRANSACTION_ACTIVE  = 1 ; some computations are deferred to the end of the transaction
     * PGSQL_TRANSACTION_INTRANS = 2 ; ready, in a transaction
     * PGSQL_TRANSACTION_INERROR = 3 ; discarded in transaction
     * PGSQL_TRANSACTION_UNKNOWN = 4 ; meaningful
     *
     * @access public
     * @link   see http://fr2.php.net/manual/en/function.pg-transaction-status.php
     * @return Integer
     */
    protected function getTransactionStatus()
    {
        return pg_transaction_status($this->connection->getHandler());
    }
}
