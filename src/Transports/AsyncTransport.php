<?php


namespace Inspector\Transports;


use Inspector\Configuration;
use Inspector\Exceptions\InspectorException;

class AsyncTransport extends AbstractApiTransport
{
    /**
     * CURL command path.
     *
     * @var string
     */
    protected $curlPath = 'curl';

    /**
     * AsyncTransport constructor.
     *
     * @param Configuration $configuration
     * @throws InspectorException
     */
    public function __construct($configuration)
    {
        if (!function_exists('proc_open')) {
            throw new InspectorException("PHP function 'proc_open' is not available, is it disabled for security reasons?");
        }

        if ('WIN' === strtoupper(substr(PHP_OS, 0, 3))) {
            throw new InspectorException('Exec transport is not supposed to work on Windows OS');
        }

        parent::__construct($configuration);
    }

    /**
     * List of available transport's options with validation regex.
     *
     * ['param-name' => 'regex']
     *
     * @return mixed
     */
    protected function getAllowedOptions()
    {
        return array_merge(parent::getAllowedOptions(), array(
            'curlPath' => '/.+/',
        ));
    }

    /**
     * Send a portion of the load to the remote service.
     *
     * @param string $data
     * @return void|mixed
     */
    public function sendChunk($data)
    {
        $cmd = "$this->curlPath -X POST";

        foreach ($this->getApiHeaders() as $name => $value) {
            $cmd .= " --header \"$name: $value\"";
        }

        $escapedData = $this->escapeArg($data);

        $cmd .= " --data '$escapedData' '".$this->config->getUrl()."' --max-time 5";
        if ($this->proxy) {
            $cmd .= " --proxy '$this->proxy'";
        }

        // return immediately while curl will run in the background
        $cmd .= ' > /dev/null 2>&1 &';

        proc_close(proc_open($cmd, [], $pipes));
    }

    /**
     * Escape character to use in the CLI.
     *
     * @param $string
     * @return mixed
     */
    protected function escapeArg($string)
    {
        // http://stackoverflow.com/a/1250279/871861
        return str_replace("'", "'\"'\"'", $string);
    }
}