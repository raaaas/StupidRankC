<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Cocur\Domain\Connection\ConnectionFactory;
use Cocur\Domain\Data\DataLoader;
use Cocur\Domain\Whois\Client;
use SEOstats\Services\Google;
use SEOstats\Services\SemRush;
use SEOstats\Services\OpenSiteExplorer;
use SEOstats\Services\Mozscape;
use SEOstats\Services\Alexa;
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    	public function checkdomain(){
			$factory = new ConnectionFactory();
			$dataLoader = new DataLoader();
			$data = $dataLoader->load(__DIR__.'/tld.json');

			$client = new Client($factory, $data);

			$var =  $client->query("ruslan.ir");
			preg_match('/expire-date:(.*)/', $var,$match);
			return $match[1];
		}

		function getKeywordSuggestionsFromGoogle($key) {
		    $keywords = $key;
		    $data = file_get_contents('http://suggestqueries.google.com/complete/search?output=firefox&client=firefox&hl=en-US&q='.urlencode($keywords));
		    if (($data = json_decode($data, true)) !== null) {
		        $keywords = $data[1];
		    }

		    dd($keywords);
		}
		public function keyworddiff($key){
			
			$checkrank = new \App\lib\GoogleRankChecker();
			$newquery               = $key;
			$useproxies             = false;
			$arrayproxies           = [];
			$googledata             = $checkrank->find($newquery, $useproxies, $arrayproxies);
			
			return $googledata;
		}

		public function keyworddiffpost(){

			

			$postdata = http_build_query(
				    array(
				        'g-recaptcha-response' => $_REQUEST['g-recaptcha-response'],
				        'q' => $_REQUEST['q'],
				        'continue' => $_REQUEST['continue'],
				        'submit' => $_REQUEST['submit']
				    )
				);

				$opts = array('http' =>
				    array(
				        'method'  => 'POST',
				        'header'  => 'Content-type: application/x-www-form-urlencoded',
				        'content' => $postdata
				    )
				);

				$context  = stream_context_create($opts);

				$result = file_get_contents($_REQUEST['continue'], false, $context);
		}
		public function sitestat($key){
			$key = 'http://'.$key;
        //'pageauth'=> $this->SEOstats->OpenSiteExplorer->getPageMetrics($link)
  		//$seostats = new \SEOstats\SEOstats;

			$seostats = new \SEOstats\SEOstats;
			echo "<b>".$key ."</b>";
			if ($seostats->setUrl($key)) {
			echo "<br>google auth : <br>";
			print_r(Mozscape::getPageAuthority());
			echo "<br>gorganic keyword: <br>";
			print_r( SemRush::getOrganicKeywords($key,"us"));
			echo "<br> SemRush page rank";
			print_r(SemRush::getDomainRank());
			echo "<br> domain rank history :<br>";
			//print_r(SemRush::getDomainRankHistory() );

			echo "<br> total link <br>";
			print_r( Mozscape::getLinkCount());
			echo "<br> competitors <br>";
			print_r( SemRush::getCompetitors());
			echo "<br>alexa monthly : <br>";
			print_r(Alexa::getMonthlyRank());			
			echo "<br>alexa daily : <br>";
			print_r(Alexa::getDailyRank());	
			echo "<br>alexa GlobalRank : <br>";
			print_r(Alexa::getGlobalRank());
			echo "<br>total index: <br>"		;
			print_r(Google::getSiteindexTotal());
			echo "<br>total moz rank : <br>";
			print_r(Mozscape::getMozRank());
			echo "<br>back link : <br>";
			print_r(Mozscape::getEquityLinkCount());
			echo "<br>alex :<br>";
			print_r(Alexa::getBacklinkCount());
			echo "<br> google <br>";
			print_r( Google::getBacklinksTotal() );

			echo "<br>".Alexa::getTrafficGraph(1);
			echo "<br>".Alexa::getTrafficGraph(6);
			echo "<br>".SemRush::getDomainGraph(1);
		}
	}
}
