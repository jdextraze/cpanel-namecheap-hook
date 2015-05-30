#!/usr/bin/php -q
<?php
define('BASE_PATH', dirname(__FILE__));

require_once(BASE_PATH . '/config.php');
require_once(BASE_PATH . '/vendor/autoload.php');

foreach($argv as $arg)
{
    if (substr($arg, 0, 2) === '--')
    {
        $action = substr($arg, 2);
        if ($action === 'describe')
        {
            echo json_encode(describe());
            exit;
        }
        if (function_exists($action))
        {
            $input_data = get_passed_data();
            list($status, $msg) = call_user_func($action, $input_data);
            echo "$status $msg";
            exit;
        }
    }
}
echo '0 cpanel-namecheap-hook need a valid switch';
exit(1);

function get_passed_data()
{
    $raw_data = '';
    $stdin_fh = fopen('php://stdin', 'r');
    if ( is_resource($stdin_fh) ) {
        stream_set_blocking($stdin_fh, 0);
        while ( ($line = fgets( $stdin_fh, 1024 )) !== false ) {
            $raw_data .= trim($line);
        }
        fclose($stdin_fh);
    }
    if ($raw_data != '') {
        $input_data = json_decode($raw_data, true);
    } else {
        $input_data = array('context'=>array(),'data'=>array(), 'hook'=>array());
    }
    return $input_data;
}

function describe()
{
    $add_zone_record = array(
        'category' => 'Cpanel',
        'event'    => 'Api2::ZoneEdit::add_zone_record',
        'stage'    => 'pre',
        'hook'     => __FILE__ . ' --add_zone_record',
        'exectype' => 'script'
    );
    $remove_zone_record = array(
        'category' => 'Cpanel',
        'event'    => 'Api2::ZoneEdit::remove_zone_record',
        'stage'    => 'pre',
        'hook'     => __FILE__ . ' --remove_zone_record',
        'exectype' => 'script'
    );
    return array($add_zone_record, $remove_zone_record);
}

function add_zone_record($input_data)
{
    global $config;

    try
    {
        $cfg = new \Namecheap\Config($config);

        /** @var Namecheap\Command\Domains\Dns\GetHosts $dnsGetHosts */
        $dnsGetHosts = Namecheap\Api::factory($cfg, 'domains.dns.getHosts');
        $dnsGetHosts->domainName($input_data['data']['args']['domain']);
        $dnsGetHosts->dispatch();

        /** @var Namecheap\Command\Domains\Dns\SetHosts $dnsSetHosts */
        $dnsSetHosts = Namecheap\Api::factory($cfg, 'domains.dns.setHosts');
        $dnsSetHosts->hosts($dnsGetHosts->hosts());
        $dnsSetHosts->domainName($input_data['data']['args']['domain']);
        $record = (new \Namecheap\DnsRecord())
            ->setHost($input_data['data']['args']['name'])
            ->setType($input_data['data']['args']['type'])
            ->setData($input_data['data']['args']['address'])
            ->setTtl($input_data['data']['args']['ttl']);
        $dnsSetHosts->addHost($record);
        $dnsSetHosts->dispatch();

        return array(1, 'Success');
    }
    catch(\Exception $ex)
    {
        return array(0, $ex->getMessage());
    }
}

function remove_zone_record()
{
    return array(0, 'Not implemented');
}