# Wordpress-Eventor-Plugin
A plugin for Wordpress that interfaces with Eventor for results and upcoming event calendar for Orienteering.

This plugin powers the "Events" tab and the "Results" tab Orienteering websites using the data from Eventor.

See http://act.orienteering.asn.au/Events and http://act.orienteering.asn.au/Results

The plugin needs to be configured with the appropriate API URL (eg. https://eventor.orienteering.asn.au/api/ ) and your API Key in Settings->Eventor.

Once configured, add one or more items in Eventor Lists (eg. Events or Results).

**OPTIONS**
  - Organisation:	select which events to list
  - Classification:	 filter events
  - Template: the template file to use to display list	
  - Mode: "Future Events" - show upcoming events, "Past Events" - show results	
  - Year [only shows for past events] : choose a year or "past 365 days"
  - Range [only shows for future events] : usually 365
  - Count:	How many events, use 0 for unlimited
  - ExtraIDs: add extra event id's not from your organisation (eg. from a neighboring state)
  
  
This plugin is used in the following websites:
- http://act.orienteering.asn.au/
- http://www.bendigo-orienteers.com.au/
- https://www.vicorienteering.asn.au/
and maybe more...?
