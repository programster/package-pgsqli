<?php

/*
 * An object to represent a postgresql result.
 */

namespace Programster\Pgsqli;


final class PgResult
{
    private $m_result;


    /**
     * Create the result object from a pgsqli result
     * This is just a wrapper to provide an interface.
     * @param type $result
     */
    public function __construct($result)
    {
        $this->m_result = $result;
    }


    /**
     * Returns the number of tuples (instances/records/rows) affected by INSERT, UPDATE, and DELETE queries.
     * https://www.php.net/manual/en/function.pg-affected-rows.php
     * @return int
     */
    public function affected_rows() : int
    {
        return pg_affected_rows($this->m_result);
    }


    /**
     * Returns an individual field of an error report
     * https://www.php.net/manual/en/function.pg-result-error-field.php
     * Returns one of the detailed error message fields associated with result resource. It is only available against
     * a PostgreSQL 7.4 or above server. The error field is specified by the fieldcode.
     * Because pg_query() and pg_query_params() return FALSE if the query fails, you must use pg_send_query() and
     * pg_get_result() to get the result handle.
     * If you need to get additional error information from failed pg_query() queries, use pg_set_error_verbosity()
     * and pg_last_error() and then parse the result.
     * @param int $fieldcode
     * @return string - A string containing the contents of the error field, NULL if the field does not exist or
     * FALSE on failure.
     */
    public function error_field(int $fieldcode)
    {
        return pg_result_error_field($this->m_result, $fieldcode);
    }


    /**
     * Get error message associated with result
     * https://www.php.net/manual/en/function.pg-result-error.php
     * @return Returns a string. Returns empty string if there is no error. If there is an error associated with the
     * result parameter, returns FALSE.
     */
    public function error()
    {
        return pg_result_error($this->m_result);
    }


    /**
     * Fetches all rows in a particular result column as an array
     * https://www.php.net/manual/en/function.pg-fetch-all-columns.php
     * @return - an array that contains all rows (records) in a particular column of the result resource. FALSE is
     * returned if the provided column value is larger than the number of columns in the result, or on any other error.
     */
    public function fetch_all_columns(int $column = 0)
    {
        return pg_fetch_all_columns($result, $column);
    }


    /**
     * Returns an object with properties that correspond to the fetched row's field names. It can optionally instantiate
     * an object of a specific class, and pass parameters to that class's constructor.
     * https://www.php.net/manual/en/function.pg-fetch-object.php
     * @param int $row - Row number in result to fetch. Rows are numbered from 0 upwards. If omitted or NULL, the next
     * row is fetched.
     * @param string $className - The name of the class to instantiate, set the properties of and return. If not
     * specified, a stdClass object is returned.
     * @param array $params - An optional array of parameters to pass to the constructor for class_name objects.
     * @return - An object with one attribute for each field name in the result. Database NULL values are returned as
     * NULL. FALSE is returned if row exceeds the number of rows in the set, there are no more rows, or on any other
     * error.
     */
    public function fetch_object(int $row = null, string $className = null, array $params = array())
    {
        $params = array($this->m_result);
        if ($row !== null) { $params[] = $row; }
        if ($className !== null) { $params[] = $className; }
        if ($params !== null) { $params[] = $params; }

        return pg_fetch_object(...$params);
    }


    /**
     * Returns the value of a particular row and field (column) in a PostgreSQL result resource.
     * https://www.php.net/manual/en/function.pg-fetch-result.php
     * @param int|string $field - A string representing the name of the field (column) to fetch, otherwise an int
     * representing the field number to fetch. Fields are numbered from 0 upwards.
     * @param int|null $row - Row number in result to fetch. Rows are numbered from 0 upwards. If left as null, next row
     * is fetched.
     * @return Boolean is returned as "t" or "f". All other types, including arrays are returned as strings formatted
     * in the same default PostgreSQL manner that you would see in the psql program. Database NULL values are returned
     * as NULL.
     * FALSE is returned if row exceeds the number of rows in the set, or on any other error.
     */
    public function fetch_result($field, ?int $row=null)
    {
        if ($row === null)
        {
            $result = pg_fetch_result($this->m_result, $field);
        }
        else
        {
            $result = pg_fetch_result($this->m_result, $row, $field);
        }

        return $result;
    }


    /**
     * Fetches one row of data from the result associated with the specified result resource.
     * https://www.php.net/manual/en/function.pg-fetch-row.php
     * @param int|null $rowNumber - Row number in result to fetch. Rows are numbered from 0 upwards. If not provided
     * a value of NULL is used resulting in the next row being fetched.
     * @return mixed - An array, indexed from 0 upwards, with each value represented as a string. Database NULL values
     * are returned as NULL. FALSE is returned if row exceeds the number of rows in the set, there are no more rows,
     * or on any other error.
     */
    public function fetch_row(?int $rowNumber = null)
    {
        return pg_fetch_row($rowNumber);
    }


    /**
     * Returns the internal storage size (in bytes) of the field number in the given PostgreSQL result.
     * https://www.php.net/manual/en/function.pg-field-size.php
     * @param int $fieldNumber - Field number, starting from 0.
     * @return int - The internal field storage size (in bytes). -1 indicates a variable length field.
     * @throws PgException - if there was an error.
     */
    public function field_size(int $fieldNumber) : int
    {
        $numBytes = pg_field_size($this->m_result, $fieldNumber);

        if ($numBytes === false)
        {
            throw new PgException();
        }

        return $numBytes;
    }


    /**
     * Returns the name of the table that field belongs to, or the table's oid if oid_only is TRUE.
     * https://www.php.net/manual/en/function.pg-field-table.php
     * @param int $fieldNumber
     * @param bool $oidOnly
     * @return the name of the table that field belongs to, or the table's oid if oid_only is TRUE.
     */
    public function field_table(int $fieldNumber, bool $oidOnly = false)
    {
        return pg_field_table($this->m_result, $fieldNumber, $oidOnly);
    }


    /**
     * Returns an integer containing the OID of the base type of the given field_number in the given PostgreSQL result
     * resource.
     * You can get more information about the field type by querying PostgreSQL's pg_type system table using the OID
     * obtained with this function. The PostgreSQL format_type() function will convert a type OID into an SQL standard
     * type name.
     * https://www.php.net/manual/en/function.pg-field-type-oid.php
     * @param int $fieldNumber - Field number, starting from 0.
     * @return - int - The OID of the field's base type.
     * @throws PgException - if there was an error.
     */
    public function field_type_oid(int $fieldNumber) : int
    {
        $result = pg_field_type_oid($this->m_result, $fieldNumber);

        if ($result === false)
        {
            throw new PgException();
        }

        return $result;
    }


    /**
     * Returns a string containing the base type name of the given field_number in the given PostgreSQL result resource.
     * https://www.php.net/manual/en/function.pg-field-type.php
     * @param int $fieldNumber - Field number, starting from 0.
     * @return string - A string containing the base name of the field's type
     */
    public function field_type(int $fieldNumber) : string
    {
        $result = pg_field_type($this->m_result, $fieldNumber);

        if ($result === false)
        {
            throw new PgException();
        }

        return $result;
    }


    /**
     * Return the number of the field number that corresponds to the field_name in the given PostgreSQL result resource.
     * https://www.php.net/manual/en/function.pg-field-num.php
     * @param string $fieldName - The name of the field.
     * @return - The field number (numbered from 0), or -1 on error.
     */
    public function field_num(string $fieldName) : int
    {
        return pg_field_num($this->m_result, $fieldName);
    }


    /**
     * Frees the memory and data associated with the specified PostgreSQL query result resource.
     * This function need only be called if memory consumption during script execution is a problem. Otherwise, all
     * result memory will be automatically freed when the script ends.
     * https://www.php.net/manual/en/function.pg-free-result.php
     * @return bool - TRUE on success or FALSE on failure.
     */
    public function free_result() : bool
    {
        return pg_free_result($this->m_result);
    }


    /**
     * Set internal row offset in result resource
     * https://www.php.net/manual/en/function.pg-result-seek.php
     * @param int $offset - Row to move the internal offset to in the result resource. Rows are numbered starting from zero.
     * @return bool - Returns TRUE on success or FALSE on failure.
     */
    public function seek(int $offset) : bool
    {
        return pg_result_seek($this->m_result, $offset);
    }


    /**
     * Get status of query result
     * https://www.php.net/manual/en/function.pg-result-status.php
     * @return PGSQL_EMPTY_QUERY, PGSQL_COMMAND_OK, PGSQL_TUPLES_OK, PGSQL_COPY_OUT,
     * PGSQL_COPY_IN, PGSQL_BAD_RESPONSE, PGSQL_NONFATAL_ERROR and PGSQL_FATAL_ERROR if PGSQL_STATUS_LONG is specified.
     * Otherwise, a string containing the PostgreSQL command tag is returned.
     */
    public function status()
    {
        return pg_result_status($this->m_result);
    }
}

