<?php 
namespace App\lib;

class GoogleRankChecker
    {
        public $start;
        public $end;

        public function __construct($start = 1, $end = 1)
        {
            $this->start = $start;
            $this->end = $end;
        }
        
        public function find($keyword, $useproxie, $proxies)
        {
            $results = [];
            $start = 0;
         //   for ($start = ($this->start-1) * 10; $start <= $this->end * 10; $start += 10) {
                $ua = [
                    0   => 'Mozilla/5.0 (Windows; U; Windows NT 6.1; rv:2.2) Gecko/20110201',
                    10  => 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
                    20  => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:40.0) Gecko/20100101 Firefox/40.1',
                    30  => 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:40.0) Gecko/20100101 Firefox/40.0'
                ];
    
                if ($useproxie) {
                    $host      = $proxies['host'];
                    $port      = $proxies['port'];
                    $username  = $proxies['username'];
                    $password  = $proxies['password'];
                    
                    if (!empty($username)) {
                        $auth     = base64_encode($username . ':' . $password);
                        $useauth  = sprintf('Proxy-Authorization: Basic %s', $auth);
                    } else {
                        $useauth  = '';
                    }
                    
                    $options = [
                        'http' => [
                            'method' => 'GET',
                            'header' => "Accept-language: en\r\n" .
                                "Cookie: PHP Google Keyword Position\r\n" .
                                "User-Agent: " . $ua[$start] . "\r\n".
                                $useauth,
                            'proxy'  => sprintf('tcp://%s:%s', $host, $port),
                            'request_fulluri' => true
                        ]
                    ];
                } else {
                    $options = [
                        'http' => [
                            'method' => 'GET',
                            'header' => "Accept-language: en\r\n" .
                                "Cookie: PHP Google Keyword Position\r\n" . 
                                "User-Agent: " . $ua[$start]
                        ]
                    ];
                }
                
                if ($useproxie) {
                    if (!empty($username)) {
                        $auth = base64_encode($username . ':' . $password);
                        $arrayproxies   = [
                            CURLOPT_PROXY        => $host,
                            CURLOPT_PROXYPORT    => $port,
                            CURLOPT_PROXYUSERPWD => $auth
                        ];
                    } else {
                        $arrayproxies   = [
                            CURLOPT_PROXY        => $host,
                            CURLOPT_PROXYPORT    => $port
                        ];
                    }
                } else {
                    $arrayproxies       = [];
                }
                
                $keyword    = str_replace(' ', '+', trim($keyword));
                $url        = sprintf('https://www.google.com/search?ie=UTF-8&q=%s&start=%s&num=10', $keyword, $start);
                $context    = stream_context_create($options);
                if ($this->_isCurlEnabled()) {
                    $data  = $this->_curl($url, $useproxie, $arrayproxies);
                } else {
                    $data  = @file_get_contents($url, false, $context);
                }
                                //echo $data;die();
                    
                if (is_array($data)) {
                    $errmsg    = $data['errmsg'];
                    $results   = ['rank' => 'zero', 'url' => $errmsg];
                } else {
                    if (strpos($data, 'To continue, please type the characters below') !== false || $data == false
                        || strpos($data, "We're sorry") !== false ) {
                        $results = ['rank' => 'zero', 'url' => ''];
                        echo $data;die();
                    } else {
                        $j = -1;
                        $i = 1;
                        
                        while (($j = stripos($data, '<cite class="_Rm">', $j+1)) !== false) {
                            $k           = stripos($data, '</cite>', $j);
                            $link        = strip_tags(substr($data, $j, $k-$j));
                            $rank        = $i++;
                            $results[]   = ['rank' => $rank,
                                             'url' => $link,
                                             'index' => $this->indexcounter($this->domainroot($link)),
                                             'keyindex'=> $this->keywordindex($this->domainroot($link),$keyword),
                                             'alexarank' => $this->getalexrank($link)];
                        }
                    }
                }
                

           // }
            
            return $results;
        }
         function domainroot($url) { 
            $host = @parse_url($url, PHP_URL_HOST);
            // If the URL can't be parsed, use the original URL
            // Change to "return false" if you don't want that
            if (!$host)
                $host = $url;
            // The "www." prefix isn't really needed if you're just using
            // this to display the domain to the user
            if (substr($host, 0, 4) == "www.")
                $host = substr($host, 4);
            // You might also want to limit the length if screen space is limited
            if (strlen($host) > 50)
                $host = substr($host, 0, 47) . '...';
            return $host;
        }
        function getalexrank($url) {
            
            $xml = simplexml_load_file('http://data.alexa.com/data?cli=10&dat=snbamz&url='.$url);
            $rank=isset($xml->SD[1]->POPULARITY)?$xml->SD[1]->POPULARITY->attributes()->TEXT:0;
            $web=(string)$xml->SD[0]->attributes()->HOST;
            return $rank;

        }
        function indexcounter($link){
            $domain =   explode("/", $link);
                $data = $this->_curl("https://www.google.com/search?q=site:".urlencode($domain[0]));       
               // $result =  stripos($data, '<div id="resultStats">');
               // $mask = '~<div class="sd" id="resultStats">About (.*) results<\/div>~is';
               // preg_match_all($mask,$data,$result);
                //preg_match("/[0-9,]+/", $result, $output);
                    $content =  $data;
                    $pattern = "/(<div id=\"resultStats\">About ([0-9,]+) results)|(<div id=\"resultStats\">([0-9,]+) results)/";
                    preg_match($pattern, $content, $out);
                    $output = preg_replace('/[^0-9]/', '', $out);

                return $output[0];
        }
        function keywordindex($link,$key){

                $domain =   explode("/", $link);



              
                $out = '';
                $data = $this->_curl("https://www.google.com/search?output=search&sclient=psy-ab&q=site:".urlencode($domain[0])."+text:".$key);   

               // $result =  stripos($data, '<div id="resultStats">');
               // $mask = '~<div class="sd" id="resultStats">About (.*) results<\/div>~is';
                //preg_match_all($mask,$data,$result);
                //preg_match("/[0-9,]+/", $result, $output);
                
                    $content =  $data;
                    $pattern = "/(<div id=\"resultStats\">About ([0-9,]+) results)|(<div id=\"resultStats\">([0-9,]+) results)|(<div id=\"resultStats\">([0-9,]+) result)/";
                    preg_match($pattern, $content, $out);
                    $output = preg_replace('/[^0-9]/', '', $out);

//                    $googlelinks = $out[count($out)];
                    //$finaly = filter_var($out[0], FILTER_SANITIZE_NUMBER_INT);

                 if(!empty($output)) {  
                    return $output[0];
                }else{ return 0;}

        }

        function get_number_of_results($line) {
            if (preg_match('/resultStats\D*([\d,]+)/', $line, $matches)) {
                //echo "$matches[1]\n";   # for debug only
                $value = (int) str_replace(',', '', $matches[1]);
                return $value;
            }  
            return -1;  
        }
        private function _isCurlEnabled()
        {
          return function_exists('curl_version');
        }

        private function _curl($url, $useproxie = null, $arrayproxies = null)
        {
            try {
                $ch = curl_init($url);

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.246');
                curl_setopt($ch, CURLOPT_AUTOREFERER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSLVERSION, 'all');

                if ($useproxie) {
                    if (!empty($arrayproxies)) {
                        foreach($arrayproxies as $param => $val) {
                            curl_setopt($ch, $param, $val);
                        }
                    }
                }

                $content = curl_exec($ch);
                $errno   = curl_errno($ch);
                $error   = curl_error($ch);
                curl_close($ch);

                if (!$errno) {
                    return $content;
                } else {
                    return [
                        'errno' => $errno,
                        'errmsg'=> $error
                    ];
                }
            } catch (Exception $e) {
                return [
                    'errno'     => $e->getCode(),
                    'errmsg'    => $e->getMessage()
                ];
            }
        }
    }

