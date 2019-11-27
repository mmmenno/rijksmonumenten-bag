# Rijksmonumenten / BAG

Deze kaartapplicatie toont rijksmonumenten, per gemeente. De data wordt betrokken van Wikidata. Als bij een monument een [BAG-pand-id](https://www.wikidata.org/wiki/Property:P5208) is vermeld, wordt uit de BAG de bijbehorende polygoon opgehaald.

Doel van de applicatie is tonen welke monumenten op Wikidata al voorzien zijn van een BAG-pand-id (dit is op het moment van schrijven alleen in Noord-Holland op enige schaal het geval) en welke monumenten onderdeel zijn van een complex. 

![monumenten in Haarlem](haarlem.png)

De eerste keer dat een gemeente bekeken wordt, wordt op de achtergrond een SPARQL query gedraaid en van de resultaten een geojsonbestand gemaakt en opgeslagen. Een volgende keer dat die gemeente bekeken wordt, wordt het opgeslagen geojsonbestand gebruikt. Wil je een bestaand geojsonbestand vervangen dan kan je dat forceren door `&uncache=true` aan de url toe te voegen.

## BAG pand id's op Wikidata

Het lijkt om verschillende redenen handig om BAG pand id's aan rijksmonumenten te koppelen:

- Betere identificatie dan adressen (ook al omdat adressen in het monumentenregister lang niet altijd overeenkomen met BAG adressen - vooral met toevoegingen gaat het vaak mis).
- Toegang tot polygonen van het pand.
- Toegang tot BAG bouwjaren (met al hun mitsen en maren).

Het is zeker niet zo dat elk rijksmonument aan één BAG id te koppelen is:

- Een rijksmonument kan met meerdere BAG id's verbonden zijn (zie bijvoorbeeld het [Woningblok van 32 woningen](https://www.wikidata.org/wiki/Q17255153) in het Rosehaghe complex).
- Meerdere rijksmonumenten kunnen in één BAG pand liggen (De [Bakenesserkerk](https://www.wikidata.org/wiki/Q2215629) en de bijbehorende [kosterswoning](https://www.wikidata.org/wiki/Q17254952) delen hetzelfde BAG pand)
- Een rijksmonument kan ook een hek, parkaanleg, tuinkoepel, brug, etc. zijn en beschikt dan logischerwijs niet over een BAG pand id.

## install

- git clone this repository
- PHP must have permission to write geojsonfiles in the `geojson` folder

