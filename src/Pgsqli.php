<?php

/*
 * A library to help with Pgsql
 */

namespace Programster\Pgsqli;

class Pgsqli
{
    private $m_connection;


    /**
     *
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $dbname
     * @param int $port
     * @throws PgException
     */
    public function __construct(
        string $host,
        string $username,
        string $password,
        string $dbname,
        int $port=5432,
        bool $useUtf8 = true,
        bool $forceNew = false,
        bool $useAsync = false
    )
    {
        if ($forceNew && $useAsync)
        {
            $forceNew = false;
        }

        $connString =
            "host=" . $host
            . " dbname=" . $db_name
            . " user=" . $user
            . " password=" . $password
            . " port=" . $port;

        if ($useUtf8)
        {
            $connString .= " options='--client_encoding=UTF8'";
        }

        if ($useAsync)
        {
            $connection = pg_connect($connString, PGSQL_CONNECT_ASYNC);
        }
        elseif ($forceNew)
        {
            $connection = pg_connect($connString, PGSQL_CONNECT_FORCE_NEW);
        }
        else
        {
            $connection = pg_connect($connString);
        }

        if ($connection == false)
        {
            throw new PgException("Failed to initialize database connection. Please check your connecton details.");
        }

        $this->m_connection = $connection;
    }


    /**
     * Gets the client encoding
     * @return - The client encoding, or FALSE on error.
     */
    public function client_encoding()
    {
        return pg_client_encoding($this->m_connection);
    }


    /**
     * Sets the client encoding and returns 0 if success or -1 if error.
     * @param string $encoding - The required client encoding. One of SQL_ASCII, EUC_JP, EUC_CN, EUC_KR, EUC_TW,
     * UNICODE, MULE_INTERNAL, LATINX (X=1...9), KOI8, WIN, ALT, SJIS, BIG5 or WIN1250.
     * The exact list of available encodings depends on your PostgreSQL version, so check your PostgreSQL manual for
     * a more specific list.
     * @return int - Returns 0 on success or -1 on error.
     */
    public function set_client_encoding(string $encoding) : int
    {
        return pg_set_client_encoding($this->m_connection, $encoding);
    }


    /**
     * Closes a PostgreSQL connection
     * https://www.php.net/manual/en/function.pg-close.php
     * @return bool - true on success, false on failure.
     */
    public function close() : bool
    {
        return pg_close($this->m_connection);
    }


    /**
     * Poll the status of an in-progress asynchronous PostgreSQL connection attempt
     * https://www.php.net/manual/en/function.pg-connect-poll.php
     * Returns PGSQL_POLLING_FAILED, PGSQL_POLLING_READING, PGSQL_POLLING_WRITING, PGSQL_POLLING_OK, or PGSQL_POLLING_ACTIVE.
     */
    public function connect_poll()
    {
        return pg_connect_poll($this->m_connection);
    }


    /**
     * Get connection is busy or not
     * https://www.php.net/manual/en/function.pg-connection-busy.php
     * @return bool - Returns TRUE if the connection is busy, FALSE otherwise.
     */
    public function connection_busy() : bool
    {
        return pg_connect_poll($this->m_connection);
    }


    /**
     * Resets the connection. It is useful for error recovery.
     * @return bool - Returns TRUE on success or FALSE on failure.
     */
    public function connection_reset() : bool
    {
        return pg_connection_reset($this->m_connection);
    }


    /**
     * Get the status of the connection.
     * @return PGSQL_CONNECTION_OK or PGSQL_CONNECTION_BAD.
     */
    public function connection_status()
    {
        return pg_connection_status($this->m_connection);
    }


    /**
     * Consumes any input waiting to be read from the database server.
     * https://www.php.net/manual/en/function.pg-consume-input.php
     * @return bool - TRUE if no error occurred, or FALSE if there was an error. Note that TRUE does not necessarily
     * indicate that input was waiting to be read.
     */
    public function consume_input() : bool
    {
        return pg_consume_input($this->m_connection);
    }


    /**
     * Checks and converts the values in assoc_array into suitable values for use in an SQL statement. Precondition for
     * pg_convert() is the existence of a table table_name which has at least as many columns as assoc_array has
     * elements. The fieldnames in table_name must match the indices in assoc_array and the corresponding datatypes
     * must be compatible. Returns an array with the converted values on success, FALSE otherwise.
     * Since PHP 5.6.0, it accepts boolean values, converting them to PostgreSQL booleans. String representations of
     * boolean values are also supported. NULL is converted to PostgreSQL NULL.
     * @param string $tableName - Name of the table against which to convert types.
     * @param array $assocArray - Data to be converted.
     * @param int $options - Any number of PGSQL_CONV_IGNORE_DEFAULT, PGSQL_CONV_FORCE_NULL or PGSQL_CONV_IGNORE_NOT_NULL, combined.
     * @return - An array of converted values.
     * @throws PgException - if there was an error.
     */
    public function convert(string $tableName, array $assocArray, int $options = 0) : array
    {
        $result = pg_convert($this->m_connection, $tableName, $assocArray, $options);

        if ($result === false)
        {
            throw new PgException();
        }

        return $result;
    }


    /**
     * Deletes records from a table specified by the keys and values in assoc_array. If options is specified,
     * pg_convert() is applied to assoc_array with the specified options.
     * If options is specified, pg_convert() is applied to assoc_array with the specified flags.
     * By default pg_delete() passes raw values. Values must be escaped or PGSQL_DML_ESCAPE option must be specified.
     * PGSQL_DML_ESCAPE quotes and escapes parameters/identifiers. Therefore, table/column names became case sensitive.
     * Note that neither escape nor prepared query can protect LIKE query, JSON, Array, Regex, etc. These parameters
     * should be handled according to their contexts. i.e. Escape/validate values.
     * @param string $tableName - Name of the table from which to delete rows.
     * @param array $data - An array whose keys are field names in the table table_name, and whose values are the values
     * of those fields that are to be deleted.
     * @param int $options - Any number of PGSQL_CONV_FORCE_NULL, PGSQL_DML_NO_CONV, PGSQL_DML_ESCAPE, PGSQL_DML_EXEC,
     * PGSQL_DML_ASYNC or PGSQL_DML_STRING combined. If PGSQL_DML_STRING is part of the options then query string is
     * returned. When PGSQL_DML_NO_CONV or PGSQL_DML_ESCAPE is set, it does not call pg_convert() internally.
     * @return TRUE on success or FALSE on failure. Returns string if PGSQL_DML_STRING is passed via options.
     */
    public function delete(string $tableName, array $data, int $options = PGSQL_DML_EXEC)
    {
        return pg_delete($this->m_connection, $tableName, $data, $options);
    }


    /**
     * Escapes a identifier (e.g. table, field names) for querying the database. It returns an escaped identifier
     * string for PostgreSQL server. pg_escape_identifier() adds double quotes before and after data. Users should not
     * add double quotes. Use of this function is recommended for identifier parameters in query. For SQL literals
     * (i.e. parameters except bytea), pg_escape_literal() or pg_escape_string() must be used. For bytea type fields,
     * pg_escape_bytea() must be used instead.
     * https://www.php.net/manual/en/function.pg-escape-identifier.php
     * @param string $data - A string containing text to be escaped.
     * @return string - A string containing the escaped data.
     */
    public function escape_identifier(string $data) : string
    {
        return pg_escape_identifier($this->m_connection, $data);
    }


    /**
     * Escapes a literal for querying the PostgreSQL database. It returns an escaped literal in the PostgreSQL format.
     * pg_escape_literal() adds quotes before and after data. Users should not add quotes. Use of this function is
     * recommended instead of pg_escape_string(). If the type of the column is bytea, pg_escape_bytea() must be used
     * instead. For escaping identifiers (e.g. table, field names), pg_escape_identifier() must be used.
     * @param string $data - the data to be escaped
     * @return string - the escaped string.
     */
    public function escape_literal(string $data) : string
    {
        return pg_escape_literal($this->m_connection, $data);
    }


    /**
     * Escape a string for query.
     * https://www.php.net/manual/en/function.pg-escape-string.php
     * It returns an escaped string in the PostgreSQL format without quotes.
     * pg_escape_literal() is more preferred way to escape SQL parameters for PostgreSQL. addslashes() must not be
     * used with PostgreSQL. If the type of the column is bytea, pg_escape_bytea() must be used instead.
     * pg_escape_identifier() must be used to escape identifiers (e.g. table names, field names)
     * @param string $string
     * @return string - the escaped string.
     */
    public function escape_string(string $string) : string
    {
        return pg_escape_string($string);
    }


    /**
     * Sends a request to execute a prepared statement with given parameters, and waits for the result.
     * pg_execute() is like pg_query_params(), but the command to be executed is specified by naming a
     * previously-prepared statement, instead of giving a query string. This feature allows commands that will be used
     * repeatedly to be parsed and planned just once, rather than each time they are executed. The statement must have
     * been prepared previously in the current session. pg_execute() is supported only against PostgreSQL 7.4 or higher
     * connections; it will fail when using earlier versions.
     * The parameters are identical to pg_query_params(), except that the name of a prepared statement is given instead
     * of a query string.
     * https://www.php.net/manual/en/function.pg-execute.php
     * @param string $stmtname - The name of the prepared statement to execute. if "" is specified, then the unnamed
     * statement is executed. The name must have been previously prepared using pg_prepare(), pg_send_prepare() or a
     * PREPARE SQL command.
     * @param array $params - An array of parameter values to substitute for the $1, $2, etc. placeholders in the
     * original prepared query string. The number of elements in the array must match the number of placeholders.
     * WARNING - Elements are converted to strings by calling this function.
     * @return PgResult
     * @throws PgQueryException - if there was a failure
     */
    public function execute(string $stmtname, array $params) : PgResult
    {
        $result = pg_execute($this->m_connection, $stmtname, $params);

        if ($result === false)
        {
            throw new PgQueryException();
        }

        return new PgResult($result);
    }


    /**
     * Flushes any outbound query data waiting to be sent on the connection.
     * @return - TRUE if the flush was successful or no data was waiting to be flushed, 0 if part of the pending data
     * was flushed but more remains or FALSE on failure.
     */
    public function flush()
    {
        return pg_flush($this->m_connection);
    }


    /**
     * Inserts the values of assoc_array into the table specified by table_name. If options is specified, pg_convert()
     * is applied to assoc_array with the specified options.
     * If options is specified, pg_convert() is applied to assoc_array with the specified flags.
     * By default pg_insert() passes raw values. Values must be escaped or PGSQL_DML_ESCAPE option must be specified.
     * PGSQL_DML_ESCAPE quotes and escapes parameters/identifiers. Therefore, table/column names became case sensitive.
     * Note that neither escape nor prepared query can protect LIKE query, JSON, Array, Regex, etc. These parameters
     * should be handled according to their contexts. i.e. Escape/validate values.
     * @param string $tableName - Name of the table into which to insert rows. The table table_name must at least have
     * as many columns as assoc_array has elements.
     * @param array $data - An array whose keys are field names in the table table_name, and whose values are the
     * values of those fields that are to be inserted.
     * @param int $options - Any number of PGSQL_CONV_OPTS, PGSQL_DML_NO_CONV, PGSQL_DML_ESCAPE, PGSQL_DML_EXEC,
     * PGSQL_DML_ASYNC or PGSQL_DML_STRING combined. If PGSQL_DML_STRING is part of the options then query string is
     * returned. When PGSQL_DML_NO_CONV or PGSQL_DML_ESCAPE is set, it does not call pg_convert() internally.
     * @return - the connection resource on success, or FALSE on failure. Returns string if PGSQL_DML_STRING is passed
     * via options.
     */
    public function insert(string $tableName , array $data, int $options = PGSQL_DML_EXEC)
    {
        return pg_insert($this->m_connection, $tableName, $data, $options);
    }


    /**
     * Returns table definition for table_name as an array.
     * @param string $tableName - the name of the table.
     * @param bool $extended - Flag for returning extended meta data.
     * @return An array of the table definition
     */
    public function meta_data(string $tableName, bool $extended = FALSE) : array
    {
        $result = pg_meta_data($this->m_connection, $tableName, $extended);

        if ($result === false)
        {
            throw new PgException("There was an issue getting metadata for table '{$tableName}'");
        }

        return $result;
    }


    /**
     * Ping database connection
     * https://www.php.net/manual/en/function.pg-ping.php
     * @return bool - true on success, false on failure.
     */
    public function ping() : bool
    {
        return pg_ping($this->m_connection);
    }


    /**
     * Submits a request to create a prepared statement with the given parameters, and waits for completion
     * https://www.php.net/manual/en/function.pg-prepare.php
     * @param string $stmtname - The name to give the prepared statement. Must be unique per-connection. If "" is
     * specified, then an unnamed statement is created, overwriting any previously defined unnamed statement.
     * @param string $query - The parameterized SQL statement. Must contain only a single statement. (multiple
     * statements separated by semi-colons are not allowed.) If any parameters are used, they are referred to as $1,
     * $2, etc.
     * @return PgResult - the resulting result object.
     * @throws PgException if there was a failure.
     */
    public function prepare(string $stmtname, string $query) : PgResult
    {
        $result = pg_prepare($this->m_connection, $stmtname, $query);

        if ($result === false)
        {
            throw new PgException();
        }

        $result = new PgResult($result);
        return $result;
    }


    /**
     *
     * @param string $query
     * @return \Programster\Pgsqli\Result
     * @throws PgQueryException
     */
    public function query(string $query) : Result
    {
        $result = pg_query($this->m_connection, $query);

        if ($result === false)
        {
            throw new PgQueryException();
        }

        return new PgResult($result);
    }


    /**
     * Updates records specified by assoc_array which has field=>value.
     * If options is specified, pg_convert() is applied to assoc_array with the specified flags.
     * By default pg_update() passes raw values. Values must be escaped or PGSQL_DML_ESCAPE option must be specified.
     * PGSQL_DML_ESCAPE quotes and escapes parameters/identifiers. Therefore, table/column names became case sensitive.
     * Note that neither escape nor prepared query can protect LIKE query, JSON, Array, Regex, etc. These parameters
     * should be handled according to their contexts. i.e. Escape/validate values.
     * https://www.php.net/manual/en/function.pg-update.php
     * @param string $tableName - Name of the table into which to update rows.
     * @param array $data - An array whose keys are field names in the table table_name, and whose values are what
     * matched rows are to be updated to.
     * @param array $condition - An array whose keys are field names in the table table_name, and whose values are the
     * conditions that a row must meet to be updated.
     * @param int $options - Any number of PGSQL_CONV_FORCE_NULL, PGSQL_DML_NO_CONV, PGSQL_DML_ESCAPE, PGSQL_DML_EXEC,
     * PGSQL_DML_ASYNC or PGSQL_DML_STRING combined. If PGSQL_DML_STRING is part of the options then query string is
     * returned. When PGSQL_DML_NO_CONV or PGSQL_DML_ESCAPE is set, it does not call pg_convert() internally.
     * @return - Returns TRUE on success or FALSE on failure. Returns string if PGSQL_DML_STRING is passed via options.
     */
    public function update(string $tableName, array $data, array $condition, int $options = PGSQL_DML_EXEC)
    {
        return pg_update($this->m_connection, $tableName, $data, $condition, $options);
    }


    /**
     * Selects records specified by assoc_array which has field=>value. For a successful query, it returns an array
     * containing all records and fields that match the condition specified by assoc_array.
     * If options is specified, pg_convert() is applied to assoc_array with the specified flags.
     * By default pg_select() passes raw values. Values must be escaped or PGSQL_DML_ESCAPE option must be specified.
     * PGSQL_DML_ESCAPE quotes and escapes parameters/identifiers. Therefore, table/column names became case sensitive.
     * Note that neither escape nor prepared query can protect LIKE query, JSON, Array, Regex, etc. These parameters
     * should be handled according to their contexts. i.e. Escape/validate values.
     * @param string $tableName - Name of the table from which to select rows.
     * @param array $assocArray - An array whose keys are field names in the table table_name, and whose values are the
     * conditions that a row must meet to be retrieved.
     * @param int $options - Any number of PGSQL_CONV_FORCE_NULL, PGSQL_DML_NO_CONV, PGSQL_DML_ESCAPE, PGSQL_DML_EXEC,
     * PGSQL_DML_ASYNC or PGSQL_DML_STRING combined. If PGSQL_DML_STRING is part of the options then query string is
     * returned. When PGSQL_DML_NO_CONV or PGSQL_DML_ESCAPE is set, it does not call pg_convert() internally.
     * @param int $resultType
     * @return TRUE on success or FALSE on failure. Returns string if PGSQL_DML_STRING is passed via options.
     */
    public function select(
        string $tableName,
        array $assocArray,
        int $options = PGSQL_DML_EXEC,
        int $resultType = PGSQL_ASSOC
    )
    {
        return pg_select($this->m_connection, $tableName, $assocArray, $options, $resultType);
    }


    /**
     * Sends a request to execute a prepared statement with given parameters, without waiting for the result(s).
     * https://www.php.net/manual/en/function.pg-send-execute.php
     * This is similar to pg_send_query_params(), but the command to be executed is specified by naming a
     * previously-prepared statement, instead of giving a query string. The function's parameters are handled
     * identically to pg_execute(). Like pg_execute(), it will not work on pre-7.4 versions of PostgreSQL.
     * @param string $stmtname - The name of the prepared statement to execute. if "" is specified, then the unnamed
     * statement is executed. The name must have been previously prepared using pg_prepare(), pg_send_prepare() or a
     * PREPARE SQL command.
     * @param array $params - An array of parameter values to substitute for the $1, $2, etc. placeholders in the
     * original prepared query string. The number of elements in the array must match the number of placeholders.
     * @return Returns TRUE on success, FALSE on failure. Use pg_get_result() to determine the query result.
     */
    public function send_execute(string $stmtname, array $params)
    {
        return pg_send_execute($this->m_connection, $stmtname, $params);
    }


    /**
     * Submits a command and separate parameters to the server without waiting for the result(s).
     * This is equivalent to pg_send_query() except that query parameters can be specified separately from the query
     * string. The function's parameters are handled identically to pg_query_params(). Like pg_query_params(), it will
     * not work on pre-7.4 PostgreSQL connections, and it allows only one command in the query string.
     * https://www.php.net/manual/en/function.pg-send-query-params.php
     *
     * @param string $query - The parameterized SQL statement. Must contain only a single statement.
     * (multiple statements separated by semi-colons are not allowed.) If any parameters are used, they are referred
     * to as $1, $2, etc.
     * @param array $params - An array of parameter values to substitute for the $1, $2, etc. placeholders in the
     * original prepared query string. The number of elements in the array must match the number of placeholders.
     * @return bool - Returns TRUE on success or FALSE on failure. Use pg_get_result() to determine the query result.
     */
    public function send_query_params(string $query, array $params) : bool
    {
        return pg_send_query_params($this->m_connection, $query, $params);
    }


    /**
     * Unescapes PostgreSQL bytea data values. It returns the unescaped string, possibly containing binary data.
     * https://www.php.net/manual/en/function.pg-unescape-bytea.php
     * @param string $data - A string containing PostgreSQL bytea data to be converted into a PHP binary string.
     * @return string
     */
    public function unescape_bytea(string $data) : string
    {
        return pg_unescape_bytea($data);
    }



    /**
     * Disable tracing of a PostgreSQL connection
     * https://www.php.net/manual/en/function.pg-untrace.php
     * @return bool - Always returns TRUE.
     */
    public function untrace() : bool
    {
        return pg_untrace($this->m_connection);
    }


    /**
     * Returns an array with the client, protocol and server version. Protocol and server versions are only available
     * if PHP was compiled with PostgreSQL 7.4 or later.
     * https://www.php.net/manual/en/function.pg-version.php
     * @return - an array with client, protocol and server keys and values (if available). Returns FALSE on error or
     * invalid connection.
     */
    public function version()
    {
        return pg_version($this->m_connection);
    }


    /**
     * Get the underlying connection resource of this object.
     * @return resource - the result of having run pg_connect when creating this object.
     */
    public function get_connection_resource()
    {
        return $this->m_connection;
    }
}