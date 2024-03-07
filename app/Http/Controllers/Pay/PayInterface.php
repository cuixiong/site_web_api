<?php
namespace App\Http\Controllers\Pay;

interface PayInterface
{
    /**
     * @param Order $order
     * @param array $options
     */
    public function do($order, $options = []);

    /**
     * @param Order $order
     * @param array $options
     */
    public function queryOrder($order, $options = []);
    
    /**
     * @param Order $order
     * @param array $options
     */
    public function refund($order, $options = []);


    /**
     * @param Order $order
     * @param array $options
     */
    public function refund_query($order, $options = []);
    
    /**
     * @param Order $order
     * @return array
     */
    public function createFormdata($order);

    /**
     * @return string
     */
    public function getActionUrl();

    /**
     * @param array $options
     * @return self
     */
    public function setOptions($options);

    /**
     * @return array
     */
    public function getOptions();

    /**
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setOption($key, $value);

    /**
     * @param string $key
     * @return mixed
     */
    public function getOption($key);
}
