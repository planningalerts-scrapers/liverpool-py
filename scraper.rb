require "icon_scraper"

IconScraper.rest_xml(
  "https://eplanning.liverpool.nsw.gov.au/Pages/XC.Track/SearchApplication.aspx",
  "d=last14days&k=LodgementDate&o=xml",
  false
)
