<?php
### Liverpool City Council scraper

require 'scraperwiki.php';
require 'simple_html_dom.php';

date_default_timezone_set('Australia/Sydney');

$url_base = "http://eplanning.liverpool.nsw.gov.au";
$comment_base = "mailto:lcc@liverpool.nsw.gov.au?subject=Development Application Enquiry: ";

    # Default to 'thisweek', use MORPH_PERIOD to change to 'thismonth' or 'lastmonth' for data recovery
    switch(getenv('MORPH_PERIOD')) {
        case 'thismonth' :
            $period = 'thismonth';
            break;
        case 'lastmonth' :
            $period = 'lastmonth';
            break;
        case 'thisweek' :
        default         :
            $period = 'thisweek';
            break;
    }

$da_page = $url_base . "/Pages/XC.Track/SearchApplication.aspx?d=" .$period. "&k=LodgementDate";

$mainUrl = scraperWiki::scrape("$da_page");
$dom = new simple_html_dom();
$dom->load($mainUrl);

# Just focus on the a section of the web site
$records = $dom->find("div[id=hiddenresult] a");

# The usual, look for the data set and if needed, save it
foreach($records as $record) {
    # request the actual DA page to get full details
    $da_page = $url_base . substr($record->href, 5);
    $da_dom = file_get_html($da_page);

    # Slow way to transform the date but it works
    $date_received = explode('Lodged: ',trim($da_dom->find('div[class=detailright]', 0)->innertext));
    $date_received = explode(' ', $date_received[1]);
    $date_received = explode('/', trim($date_received[0]));
    $date_received = "$date_received[2]-$date_received[1]-$date_received[0]";

    # Prep some data before hand
    $desc = explode('<br />', $da_dom->find('div[class=detailright]', 0)->innertext);
    $desc = trim(html_entity_decode($desc[0]));
    $desc = trim(preg_replace('/\s+/', ' ', $desc));
    $desc = ucwords(strtolower($desc));
    
    # council_reference
    $council_reference = trim(html_entity_decode($da_dom->find('h2',0)->plaintext));
    
    # address
    $address = trim(html_entity_decode($da_dom->find('div[class=detailright] a', 0)->plaintext));
    $address = trim(preg_replace('/\s+/', ' ', $address));
    $address = $address . ', Australia';

    # comment
    $comment = $comment_base . $council_reference;
    
    # Put all information in an array
    $application = array (
        'council_reference' => $council_reference,
        'address'           => $address,
        'description'       => $desc,
        'info_url'          => $da_page,
        'comment_url'       => $comment,
        'date_scraped'      => date('Y-m-d'),
        'date_received'     => date('Y-m-d', strtotime($date_received))
    );

    # Check if record exist, if not, INSERT, else do nothing
    $existingRecords = scraperwiki::select("* from data where `council_reference`='" . $application['council_reference'] . "'");
    if (count($existingRecords) == 0) {
        print ("Saving record " . $application['council_reference'] . "\n");
        # print_r ($application);
        scraperwiki::save(array('council_reference'), $application);
    } else {
        print ("Skipping already saved record " . $application['council_reference'] . "\n");
    }
}

?>
