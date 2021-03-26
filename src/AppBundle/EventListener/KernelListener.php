<?php

namespace AppBundle\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class KernelListener
{
    private $orm;
    public  function __construct($orm)
    {
        $this->orm = $orm;
    }

    public function onKernelRequest(FilterControllerEvent  $event)
    {
        if ($event->getRequestType() !== \Symfony\Component\HttpKernel\HttpKernel::MASTER_REQUEST) {
            return;
        }
        
        $repo = $this->orm->getRepository('AppBundle:IPWhitelist');
          
        $items = $repo->findAll();
        
        $ret = array();
        
        $ip_address = $event->getRequest()->getClientIp();
        $arr_ip_numbers = explode('.', $ip_address);
        $found = 0;
        
        foreach($items as $item)
        {
            $match_found = 0;
            $ret = $item->getIndirizzoIp();
            $ret = explode('.', $ret);
            
            $i = 0;
            foreach($ret as $key => $value){

                if ($value=='*') $match_found++;
                else if ((is_numeric($value)) && ($value == $arr_ip_numbers[$i])){
                    $match_found++;
                }
                else if ($value[0]=='['){
                    $range = substr($value, 1, count($value)-2);

                    $arrRange = explode('-', $range);

                    if (($arr_ip_numbers[$i]>=$arrRange[0]) && ($arr_ip_numbers[$i]<=$arrRange[1])) $match_found++;
                }
                
                $i++;
            }

            if ($match_found == 4) { $found = 1; break;}
            
        }
        
    if (!$found) throw new AccessDeniedHttpException('Your IP is not valid!');                 
    
    }
}