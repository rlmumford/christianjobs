<?php

namespace Drupal\job_board\EventSubscriber;

use CommerceGuys\Addressing\AddressFormat\AdministrativeAreaType;
use Drupal\address\Event\AddressEvents;
use Drupal\address\Event\AddressFormatEvent;
use Drupal\address\Event\SubdivisionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds a county field and a predefined list of counties for Great Britain.
 *
 * Counties are not provided by the library because they're not used for
 * addressing. However, sites might want to add them for other purposes.
 */
class GreatBritainAdministrativeAreasEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AddressEvents::ADDRESS_FORMAT][] = ['onAddressFormat'];
    $events[AddressEvents::SUBDIVISIONS][] = ['onSubdivisions'];
    return $events;
  }

  /**
   * Alters the address format for Great Britain.
   *
   * @param \Drupal\address\Event\AddressFormatEvent $event
   *   The address format event.
   */
  public function onAddressFormat(AddressFormatEvent $event) {
    $definition = $event->getDefinition();
    if ($definition['country_code'] == 'GB') {
      $definition['format'] = $definition['format'] . "\n%administrativeArea";
      $definition['administrative_area_type'] = AdministrativeAreaType::COUNTY;
      $definition['subdivision_depth'] = 1;
      $event->setDefinition($definition);
    }
  }

  /**
   * Provides the subdivisions for Great Britain.
   *
   * Note: Provides just the Welsh counties. A real subscriber would include
   * the full list, sourced from the CLDR "Territory Subdivisions" listing.
   *
   * @param \Drupal\address\Event\SubdivisionsEvent $event
   *   The subdivisions event.
   */
  public function onSubdivisions(SubdivisionsEvent $event) {
    // For administrative areas $parents is an array with just the country code.
    // Otherwise it also contains the parent subdivision codes. For example,
    // if we were defining cities in California, $parents would be ['US', 'CA'].
    $parents = $event->getParents();
    if ($event->getParents() != ['GB']) {
      return;
    }

    $definitions = [
      'country_code' => $parents[0],
      'parents' => $parents,
      'subdivisions' => [],
    ];

    $sub_divisions = [
      'Aberdeen City' => 'GB-ABE',
      'Aberdeenshire' => 'GB-ABD',
      'Angus' => 'GB-ANS',
      'Antrim and Newtownabbey' => 'GB-ANN',
      'Ards and North Down' => 'GB-AND',
      'Argyll and Bute' => 'GB-AGB',
      'Armagh, Banbridge and Craigavon' => 'GB-ABC',
      'Barking and Dagenham' => 'GB-BDG',
      'Barnet' => 'GB-BNE',
      'Barnsley' => 'GB-BNS',
      'Bath and North East Somerset' => 'GB-BAS',
      'Bedford' => 'GB-BDF',
      'Belfast' => 'GB-BFS',
      'Bexley' => 'GB-BEX',
      'Birmingham' => 'GB-BIR',
      'Blackburn with Darwen' => 'GB-BBD',
      'Blackpool' => 'GB-BPL',
      'Blaenau Gwent' => 'GB-BGW',
      'Bolton' => 'GB-BOL',
      'Bournemouth' => 'GB-BMH',
      'Bracknell Forest' => 'GB-BRC',
      'Bradford' => 'GB-BRD',
      'Brent' => 'GB-BEN',
      'Bridgend [Pen-y-bont ar Ogwr]' => 'GB-BGE',
      'Brighton and Hove' => 'GB-BNH',
      'Bristol, City of' => 'GB-BST',
      'Bromley' => 'GB-BRY',
      'Buckinghamshire' => 'GB-BKM',
      'Bury' => 'GB-BUR',
      'Caerphilly [Caerffili]' => 'GB-CAY',
      'Calderdale' => 'GB-CLD',
      'Cambridgeshire' => 'GB-CAM',
      'Camden' => 'GB-CMD',
      'Cardiff [Caerdydd]' => 'GB-CRF',
      'Carmarthenshire [Sir Gaerfyrddin]' => 'GB-CMN',
      'Causeway Coast and Glens' => 'GB-CCG',
      'Central Bedfordshire' => 'GB-CBF',
      'Ceredigion [Sir Ceredigion]' => 'GB-CGN',
      'Cheshire East' => 'GB-CHE',
      'Cheshire West and Chester' => 'GB-CHW',
      'Clackmannanshire' => 'GB-CLK',
      'Conwy' => 'GB-CWY',
      'Cornwall' => 'GB-CON',
      'Coventry' => 'GB-COV',
      'Croydon' => 'GB-CRY',
      'Cumbria' => 'GB-CMA',
      'Darlington' => 'GB-DAL',
      'Denbighshire [Sir Ddinbych]' => 'GB-DEN',
      'Derby' => 'GB-DER',
      'Derbyshire' => 'GB-DBY',
      'Derry and Strabane' => 'GB-DRS',
      'Devon' => 'GB-DEV',
      'Doncaster' => 'GB-DNC',
      'Dorset' => 'GB-DOR',
      'Dudley' => 'GB-DUD',
      'Dumfries and Galloway' => 'GB-DGY',
      'Dundee City' => 'GB-DND',
      'Durham, County' => 'GB-DUR',
      'Ealing' => 'GB-EAL',
      'East Ayrshire' => 'GB-EAY',
      'East Dunbartonshire' => 'GB-EDU',
      'East Lothian' => 'GB-ELN',
      'East Renfrewshire' => 'GB-ERW',
      'East Riding of Yorkshire' => 'GB-ERY',
      'East Sussex' => 'GB-ESX',
      'Edinburgh, City of' => 'GB-EDH',
      'Eilean Siar' => 'GB-ELS',
      'Enfield' => 'GB-ENF',
      'Essex' => 'GB-ESS',
      'Falkirk' => 'GB-FAL',
      'Fermanagh and Omagh' => 'GB-FMO',
      'Fife' => 'GB-FIF',
      'Flintshire [Sir y Fflint]' => 'GB-FLN',
      'Gateshead' => 'GB-GAT',
      'Glasgow City' => 'GB-GLG',
      'Gloucestershire' => 'GB-GLS',
      'Greenwich' => 'GB-GRE',
      'Gwynedd' => 'GB-GWN',
      'Hackney' => 'GB-HCK',
      'Halton' => 'GB-HAL',
      'Hammersmith and Fulham' => 'GB-HMF',
      'Hampshire' => 'GB-HAM',
      'Haringey' => 'GB-HRY',
      'Harrow' => 'GB-HRW',
      'Hartlepool' => 'GB-HPL',
      'Havering' => 'GB-HAV',
      'Herefordshire' => 'GB-HEF',
      'Hertfordshire' => 'GB-HRT',
      'Highland' => 'GB-HLD',
      'Hillingdon' => 'GB-HIL',
      'Hounslow' => 'GB-HNS',
      'Inverclyde' => 'GB-IVC',
      'Isle of Anglesey [Sir Ynys Môn]' => 'GB-AGY',
      'Isle of Wight' => 'GB-IOW',
      'Isles of Scilly' => 'GB-IOS',
      'Islington' => 'GB-ISL',
      'Kensington and Chelsea' => 'GB-KEC',
      'Kent' => 'GB-KEN',
      'Kingston upon Hull' => 'GB-KHL',
      'Kingston upon Thames' => 'GB-KTT',
      'Kirklees' => 'GB-KIR',
      'Knowsley' => 'GB-KWL',
      'Lambeth' => 'GB-LBH',
      'Lancashire' => 'GB-LAN',
      'Leeds' => 'GB-LDS',
      'Leicester' => 'GB-LCE',
      'Leicestershire' => 'GB-LEC',
      'Lewisham' => 'GB-LEW',
      'Lincolnshire' => 'GB-LIN',
      'Lisburn and Castlereagh' => 'GB-LBC',
      'Liverpool' => 'GB-LIV',
      'London, City of' => 'GB-LND',
      'Luton' => 'GB-LUT',
      'Manchester' => 'GB-MAN',
      'Medway' => 'GB-MDW',
      'Merthyr Tydfil [Merthyr Tudful]' => 'GB-MTY',
      'Merton' => 'GB-MRT',
      'Mid and East Antrim' => 'GB-MEA',
      'Mid Ulster' => 'GB-MUL',
      'Middlesbrough' => 'GB-MDB',
      'Midlothian' => 'GB-MLN',
      'Milton Keynes' => 'GB-MIK',
      'Monmouthshire [Sir Fynwy]' => 'GB-MON',
      'Moray' => 'GB-MRY',
      'Neath Port Talbot [Castell-nedd Port Talbot]' => 'GB-NTL',
      'Newcastle upon Tyne' => 'GB-NET',
      'Newham' => 'GB-NWM',
      'Newport [Casnewydd]' => 'GB-NWP',
      'Newry, Mourne and Down' => 'GB-NMD',
      'Norfolk' => 'GB-NFK',
      'North Ayrshire' => 'GB-NAY',
      'North East Lincolnshire' => 'GB-NEL',
      'North Lanarkshire' => 'GB-NLK',
      'North Lincolnshire' => 'GB-NLN',
      'North Somerset' => 'GB-NSM',
      'North Tyneside' => 'GB-NTY',
      'North Yorkshire' => 'GB-NYK',
      'Northamptonshire' => 'GB-NTH',
      'Northumberland' => 'GB-NBL',
      'Nottingham' => 'GB-NGM',
      'Nottinghamshire' => 'GB-NTT',
      'Oldham' => 'GB-OLD',
      'Orkney Islands' => 'GB-ORK',
      'Oxfordshire' => 'GB-OXF',
      'Pembrokeshire [Sir Benfro]' => 'GB-PEM',
      'Perth and Kinross' => 'GB-PKN',
      'Peterborough' => 'GB-PTE',
      'Plymouth' => 'GB-PLY',
      'Poole' => 'GB-POL',
      'Portsmouth' => 'GB-POR',
      'Powys' => 'GB-POW',
      'Reading' => 'GB-RDG',
      'Redbridge' => 'GB-RDB',
      'Redcar and Cleveland' => 'GB-RCC',
      'Renfrewshire' => 'GB-RFW',
      'Rhondda, Cynon, Taff [Rhondda, Cynon, Taf]' => 'GB-RCT',
      'Richmond upon Thames' => 'GB-RIC',
      'Rochdale' => 'GB-RCH',
      'Rotherham' => 'GB-ROT',
      'Rutland' => 'GB-RUT',
      'Salford' => 'GB-SLF',
      'Sandwell' => 'GB-SAW',
      'Scottish Borders, The' => 'GB-SCB',
      'Sefton' => 'GB-SFT',
      'Sheffield' => 'GB-SHF',
      'Shetland Islands' => 'GB-ZET',
      'Shropshire' => 'GB-SHR',
      'Slough' => 'GB-SLG',
      'Solihull' => 'GB-SOL',
      'Somerset' => 'GB-SOM',
      'South Ayrshire' => 'GB-SAY',
      'South Gloucestershire' => 'GB-SGC',
      'South Lanarkshire' => 'GB-SLK',
      'South Tyneside' => 'GB-STY',
      'Southampton' => 'GB-STH',
      'Southend-on-Sea' => 'GB-SOS',
      'Southwark' => 'GB-SWK',
      'St. Helens' => 'GB-SHN',
      'Staffordshire' => 'GB-STS',
      'Stirling' => 'GB-STG',
      'Stockport' => 'GB-SKP',
      'Stockton-on-Tees' => 'GB-STT',
      'Stoke-on-Trent' => 'GB-STE',
      'Suffolk' => 'GB-SFK',
      'Sunderland' => 'GB-SND',
      'Surrey' => 'GB-SRY',
      'Sutton' => 'GB-STN',
      'Swansea [Abertawe]' => 'GB-SWA',
      'Swindon' => 'GB-SWD',
      'Tameside' => 'GB-TAM',
      'Telford and Wrekin' => 'GB-TFW',
      'Thurrock' => 'GB-THR',
      'Torbay' => 'GB-TOB',
      'Torfaen [Tor-faen]' => 'GB-TOF',
      'Tower Hamlets' => 'GB-TWH',
      'Trafford' => 'GB-TRF',
      'Vale of Glamorgan, The [Bro Morgannwg]' => 'GB-VGL',
      'Wakefield' => 'GB-WKF',
      'Walsall' => 'GB-WLL',
      'Waltham Forest' => 'GB-WFT',
      'Wandsworth' => 'GB-WND',
      'Warrington' => 'GB-WRT',
      'Warwickshire' => 'GB-WAR',
      'West Berkshire' => 'GB-WBK',
      'West Dunbartonshire' => 'GB-WDU',
      'West Lothian' => 'GB-WLN',
      'West Sussex' => 'GB-WSX',
      'Westminster' => 'GB-WSM',
      'Wigan' => 'GB-WGN',
      'Wiltshire' => 'GB-WIL',
      'Windsor and Maidenhead' => 'GB-WNM',
      'Wirral' => 'GB-WRL',
      'Wokingham' => 'GB-WOK',
      'Wolverhampton' => 'GB-WLV',
      'Worcestershire' => 'GB-WOR',
      'Wrexham [Wrecsam]' => 'GB-WRX',
      'York' => 'GB-YOR',
    ];

    foreach ($sub_divisions as $name => $iso) {
      $definitions['subdivisions'][$name] = [
        'iso_code' => $iso
      ];
    }

    $event->setDefinitions($definitions);
  }

}
