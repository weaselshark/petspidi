<?php

namespace Fuel\Tasks;

class Crawl
{

    /**
     * This method gets ran when a valid method name is not used in the command.
     *
     * Usage (from command line):
     *
     * php oil r crawl
     *
     * or
     *
     * php oil r crawl "hello"
     *
     * @return string
     */
    public static function run($url = null) {

    }

    /**
     *
     * Usage (from command line):
     *
     * php oil r crawl:protect
     *
     * @return string
     */
    public static function protect() {
        
        //$eye = \Cli::color("*", 'green');
    }
    
    public static function book($url = null) {
        
        $query = \DB::select('*')->from('book_url')->where('crawled', '0')->order_by('id', 'asc')->limit(1);
        $url_array = $query->execute()->as_array();
                
        $url_id = $url_array[0]['id'];
        $category = $url_array[0]['category'];
        $url = $url_array[0]['url'];
                
        $xmlDocMn = new \DOMDocument();
        @$xmlDocMn->loadHTML(file_get_contents($url));
        $xpathMn = new \DOMXPath($xmlDocMn);
        
        $bookrows= $xpathMn->query("//a[@class='bookTitle']/@href");
        
        foreach ($bookrows as $brow) {
            
            try {
            
                $bookurl = 'https://www.goodreads.com'. $brow->nodeValue;

                $xmlDoc = new \DOMDocument();
                @$xmlDoc->loadHTML(file_get_contents($bookurl));
                $xpath = new \DOMXPath($xmlDoc);

                $rating = $xpath->query("//span[@itemprop='ratingValue']");
                $title = $xpath->query("//h1[@id='bookTitle']");
                $author = $xpath->query("//span[@itemprop='name']");
                $num_of_rating = $xpath->query("//span[@itemprop='ratingCount']/@title");
                $isbn = $xpath->query("//span[@itemprop='isbn']");
                $awards = $xpath->query("//div[@itemprop='awards']");
                $pages = $xpath->query("//span[@itemprop='numberOfPages']");
                $description = $xpath->query("//div[@id='description']");

                $rows = $xpath->query("//div[@class='row']");
                $published = "";
                foreach ($rows as $row) {
                    $cur_val = $row?$row->nodeValue:"";
                    //strpos retuns null if the second parameter doesn't exist inside first
                    if (!strpos($cur_val, "Published") === FALSE) {
                        $published = $cur_val;
                    }
                }

                $query = \DB::insert('book');

                // Set the columns and values
                $query->set(array(
                    'title' => trim(($title && $title->item(0)->nodeValue)?$title->item(0)->nodeValue:''),
                    'author' => trim(($author && $author->item(0))?$author->item(0)->nodeValue:''),
                    'isbn' => trim(($isbn && $isbn->item(0))?$isbn->item(0)->nodeValue:''),
                    'rating' => trim(($rating && $rating->item(0))?$rating->item(0)->nodeValue:''),
                    'num_of_rating' => trim(($num_of_rating && $num_of_rating->item(0))?$num_of_rating->item(0)->nodeValue:''),
                    'awards' => trim(($awards && $awards->item(0))?$awards->item(0)->nodeValue:''),
                    'description' => trim(($description && $description->item(0))?$description->item(0)->nodeValue:''),
                    'published' => trim(preg_replace('/\s+/', ' ',$published)),
                    'category' => trim($category),
                    'num_of_pages' => trim(($pages && $pages->item(0))?$pages->item(0)->nodeValue:''),
                ));

                $query->execute();
            } catch (\Exception $e) {
                continue;
            }
        }
        
        \DB::update('book_url')->value('crawled', '1')->where('id', $url_id)->execute();
    }
}
