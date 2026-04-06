<?php

return [
    'metal_mine' => [
        'title'            => 'Metaalmijn',
        'description'      => 'Gebruikt bij de winning van metaalertsen, metaalmijnen zijn van primair belang voor alle opkomende en gevestigde rijken.',
        'description_long' => 'Metaalmijnen verzorgen de basisgrondstoffen van een opkomend koninkrijk en verzorgen de bouw van gebouwen en schepen. Metaal is de goedkoopste beschikbare grondstof en er is niet veel energie nodig om metaal te winnen, maar metaal wordt meer gebruikt dan andere grondstoffen. Metaal wordt diep ondergronds gevonden, wat leidt tot diepere en diepere mijnen die weer meer energie nodig hebben.',
    ],

    'crystal_mine' => [
        'title'            => 'Kristalmijn',
        'description'      => 'Kristallen zijn de belangrijkste grondstof die wordt gebruikt om elektronische schakelingen te bouwen en bepaalde legeringen te vormen.',
        'description_long' => 'De kristalmijn levert de hoofdgrondstof voor elektronische circuits en legeringen. Hij verbruikt bij het bouwen ongeveer anderhalf keer zoveel energie als de metaalmijn, wat kristal duidelijk waardevoller maakt. Kristal is nodig voor bijna alle schepen en alle gebouwen. Het meeste van het voor de schepen gebruikte kristal is echter zeldzaam en net zoals metaal in grotere hoeveelheid alleen vindbaar op grotere dieptes. Een uitbouw van de mijn naar grotere diepten verhoogt daarom ook de hoeveelheid aan vindbaar kristal.',
    ],

    'deuterium_synthesizer' => [
        'title'            => 'Deuteriumfabriek',
        'description'      => 'Deuteriumsynthesizers onttrekken het spoor-deuteriumgehalte uit het water op een planeet.',
        'description_long' => 'Deuterium wordt ook wel zwaar water genoemd. (officieel is de volledige benaming dideuteriumoxide)
Het is chemisch gezien identiek aan water, maar de waterstofatomen zijn vervangen door deuteriumatomen.
Het waterstof atoom bevat een extra neutron en is erg geschikt als brandstof voor schepen vanwege de hoge energie opbrengst van de D-T reactie. Zwaar water wordt vaak gevonden in de diepzee vanwege het moleculaire gewicht. Het verbeteren van de deuteriumfabriek zorgt ervoor dat je deze grondstoffen kunt oogsten.
Deuterium wordt gebruikt als brandstof, voor onderzoek, voor een blik in de melkweg én voor de phalanx.',
    ],

    'solar_plant' => [
        'title'            => 'Zonne-energiecentrale',
        'description'      => 'Zonneenergiecentrales absorberen energie uit zonnestraling. Alle mijnen hebben energie nodig om te functioneren.',
        'description_long' => 'Om de door gebouwen benodigde energie te produceren zijn enorme energiecentrales nodig. Een zonne-energiecentrale is een van de manieren om energie te maken. 
Een zonnecentrale gebruikt halfgeleiders voor fotovoltaïsche cellen die fotonen omzetten naar elektrische energie. Als de zonne-energiecentrale verbeterd wordt, neemt hij een groter oppervlak in gebruik om zonlicht op te vangen en in stroom om te zetten. Zo wordt er dus meer energie geproduceerd. Zonne-energiecentrales verzorgen meestal de hoofdmoot van de energiebehoefte van een planeet.',
    ],

    'fusion_plant' => [
        'title'            => 'Fusiecentrale',
        'description'      => 'De fusiereactor gebruikt deuterium om energie te produceren.',
        'description_long' => 'De kernfusiereactor vormt uit twee deuteriumatomen een heliumatoom. Hierbij wordt gebruik gemaakt van extreem hoge temperaturen en druk. Elke gram deuterium produceert 172 MWh aan energie; dus hoe groter de fusiereactor, des te meer parallelle fusiereacties er plaats kunnen vinden die de hoeveelheid energie verhogen dat geproduceerd wordt. Grote centrales gebruiken meer deuterium en produceren meer energie. Het verhogen van je energietechniek maakt de centrale efficiënter.De energieproductie is te berekenen met de volgende formule: 30 * [level fusiecentrale] * (1,05 + [niveau Energietechniek] * 0,01) ^ [level fusiecentrale].',
    ],

    'metal_store' => [
        'title'            => 'Metaalopslag',
        'description'      => 'Biedt opslagcapaciteit voor overtollig metaal.',
        'description_long' => 'Ruw en onverwerkt metaal wordt in deze gigantische opslagruimtes bewaard. Des te hoger het niveau, des te meer metaal er opgeslagen kan worden. Zodra de opslagplaatsen vol zijn kan er geen metaal meer gewonnen worden door de metaalmijn op deze planeet.

De opslagtanks voor metaal beschermen een percentage (maximaal 10%) van de dagelijkse metaalmijnopbrengst.',
    ],

    'crystal_store' => [
        'title'            => 'Kristalopslag',
        'description'      => 'Biedt opslagcapaciteit voor overtollig kristal.',
        'description_long' => 'Onverwerkt ruw kristal wordt in deze gigantische opslagruimtes bewaard. Des te hoger het niveau, des te meer kristal er opgeslagen kan worden. Zodra de opslagplaatsen vol zijn kan er geen kristal meer gewonnen worden door de kristalmijn op deze planeet.

De opslagtanks voor kristal beschermen een percentage (maximaal 10%) van de dagelijkse kristalmijnopbrengst.',
    ],

    'deuterium_store' => [
        'title'            => 'Deuteriumtank',
        'description'      => 'Reusachtige tanks voor de opslag van nieuw gewonnen deuterium.',
        'description_long' => 'Dit zijn enorme opslagtanks die gebruikt worden om overtollig deuterium in op te slaan. Deze tanks zijn vaak te vinden in de buurt van scheepswerven aangezien deuterium gebruikt wordt als brandstof. Des te hoger het niveau, des te meer deuterium er opgeslagen kan worden. Zodra de opslagplaatsen vol zijn kan er geen deuterium meer gewonnen worden door de deuteriummijn op deze planeet.

De opslagtanks voor deuterium beschermen een percentage (maximaal 10%) van de dagelijkse deuteriumproductie.',
    ],

    // -------------------------------------------------------------------------
    // Station / Facilities objects (from StationObjects.php)
    // -------------------------------------------------------------------------

    'robot_factory' => [
        'title'            => 'Robotfabriek',
        'description'      => 'Robotfabrieken leveren bouwrobots om te helpen bij de bouw van gebouwen. Elk niveau verhoogt de bouwsnelheid van gebouwen.',
        'description_long' => 'Robotfabrieken produceren goedkope en betrouwbare helpers die gebruikt kunnen worden om gebouwen te bouwen of te verbeteren. Elk niveau van verbetering van de robotfabriek verhoogt de efficiëntie en het aantal robots dat helpt met bouwen.',
    ],

    'shipyard' => [
        'title'            => 'Werf',
        'description'      => 'Alle soorten schepen en verdedigingsinstallaties worden gebouwd in de planetaire scheepswerf.',
        'description_long' => 'De werf is verantwoordelijk voor de productie van ruimteschepen en verdedigingssystemen. Wanneer de werf groter wordt, kan deze een grotere reeks schepen steeds sneller produceren. Als een nanorobotfabriek beschikbaar is op de planeet wordt de bouwsnelheid van schepen en verdedigingssystemen enorm versneld.',
    ],

    'research_lab' => [
        'title'            => 'Onderzoekslab',
        'description'      => 'Een onderzoekslaboratorium is vereist om onderzoek te doen naar nieuwe technologieën.',
        'description_long' => 'Om nieuwe technologieën te onderzoeken heb je een onderzoekslab nodig. Het aantal upgrades van het lab verhoogt niet alleen de snelheid waarmee nieuwe technologieën geleerd worden maar opent ook compleet nieuwe gebieden van onderzoek. Om zo snel mogelijk onderzoek te doen wordt al het onderzoekspersoneel in het hele koninkrijk naar de planeet gestuurd waar de onderzoekstaak gestart is. Zodra de taak klaar is gaan de onderzoekers terug naar hun thuisplaneten en brengen de nieuwe techniek met zich mee terug. Op deze manier wordt kennis over nieuwe technieken gemakkelijk verspreid door het hele koninkrijk.',
    ],

    'alliance_depot' => [
        'title'            => 'Alliantiehangar',
        'description'      => 'Het alliantiedepot levert brandstof aan bevriende vloten in een baan om de planeet die helpen bij de verdediging.',
        'description_long' => 'De alliantie hangar biedt de mogelijkheid aan bevriende vloten die meehelpen met verdedigen om bij te tanken. Elk niveau van verbetering biedt de schepen in een baan om de planeet zitten extra eenheden deuterium per uur.',
    ],

    'missile_silo' => [
        'title'            => 'Raketsilo',
        'description'      => 'Raketensilo\'s worden gebruikt voor de opslag van raketten.',
        'description_long' => 'Een raketsilo is een lanceer- en opslaginrichting voor raketten. Er is ruimte voor 5 interplanetaire of 10 anti-ballistische raketten per niveau van een silo. Opslag van beide typen door elkaar is mogelijk; 1 interplanetaire raket gebruikt 2 plaatsen in vergelijking met anti-ballistische raketten.',
    ],

    'nano_factory' => [
        'title'            => 'Nanorobotfabriek',
        'description'      => 'Dit is het toppunt van robotica-technologie. Elk niveau verkort de bouwtijd voor gebouwen, schepen en verdedigingen.',
        'description_long' => 'Nanorobots zijn kleine robots met een gemiddelde grootte van enkele nanometers. Deze mechanische microben zijn verbonden in een netwerk en zijn geprogrammeerd voor een constructietaak en leveren een ongekende bouwsnelheid. Nanorobots opereren op moleculair niveau en zijn enorm handig voor het bouwen van schepen omdat ze onderdeel blijven van de structuur van het schip en op deze manier kunnen hun reparatiemogelijkheden gebruikt worden voor schadecontrole en reparaties als ze genoeg energie en grondstoffen krijgen. Ieder niveau van dit gebouw verlaagt de bouwtijd van gebouwen, schepen en defensie aanzienlijk.',
    ],

    'terraformer' => [
        'title'            => 'Terravormer',
        'description'      => 'De terraformer vergroot het bruikbare oppervlak van planeten.',
        'description_long' => 'Met de toenemende bouw op planeten, wordt zelfs de leefruimte voor de kolonie alsmaar beperkter. Traditionele methoden zoals hoogbouw of ondergrondse constructiewerken blijken ook steeds minder toereikend. Een kleine groep van krachtenergie-fysici en nano-ingenieurs kwam uiteindelijk met de oplossing: terravormen.Door gebruik te maken van gigantische hoeveelheden energie, kan de terravormer hele stroken land of zelfs continenten bebouwbaar maken. Dit gebouw huisvest de productie van nanieten die speciaal met dit doel gemaakt worden, en zo waarborgen zij een consistente grondkwaliteit.
Elk terravormerlevel maakt het mogelijk om 5 velden te bewerken. Met elk level bezet de terravormer zelf één veld. Voor elke 2 terravormerlevels ontvang je 1 bonusveld.Eenmaal gebouwd kan de terravormer niet meer ontmanteld worden.',
    ],

    'space_dock' => [
        'title'            => 'Ruimtewerf',
        'description'      => 'Wrakken kunnen worden gerepareerd in het Ruimtedok.',
        'description_long' => 'De Ruimtewerf biedt de mogelijkheid om schepen die vernietigd werden in gevecht en een wrak achterlieten te repareren. De hersteltijd neemt maximaal 12 uren in beslag, maar het duurt minstens 30 minuten tot de schepen terug in dienst genomen kunnen worden.

Herstellingen moeten gestart worden binnen de 3 dagen nadat het wrak gemaakt werd. De herstelde schepen moeten manueel terug in dienst genomen worden achter de reparatie. Als dit niet uitgevoerd wordt, zullen individuele schepen van gelijk welk type terug in dienst genomen worden na 3 dagen.
Wrakstukken verschijnen alleen als er meer dan 150.000 eenheden zijn vernietigd.

Aangezien de Ruimtewerf zich in de ruimte bevindt, heeft deze geen planeetveld nodig.',
    ],

    'lunar_base' => [
        'title'            => 'Maanbasis',
        'description'      => 'Omdat de maan geen atmosfeer heeft, is een maanbasis vereist om bewoonbare ruimte te creëren.',
        'description_long' => 'Aangezien de maan geen atmosfeer heeft is een maanbasis nodig om bewoonbare ruimtes te maken. De maanbasis zorgt niet alleen voor de benodigde zuurstof maar ook voor kunstmatige zwaartekracht, verwarming en bescherming. Des te hoger het niveau van de maanbasis, des te groter het gebied om gebouwen op te plaatsen. Elk niveau van de maanbasis zorgt voor 3 maanvelden totdat de maan volledig volgebouwd is.Als de maanbasis eenmaal gebouwd is kan het niet afgebroken worden.',
    ],

    'sensor_phalanx' => [
        'title'            => 'Sensorphalanx',
        'description'      => 'Met de sensorfalanx kunnen vloten van andere rijken worden ontdekt en geobserveerd. Hoe groter de sensorfalanx, hoe groter het bereik dat gescand kan worden.',
        'description_long' => 'Een reeks sensors met hoge resolutie wordt gebruikt om een enorm frequentiespectrum te scannen. Enorm parallelle verwerkingseenheden analyseren vervolgens de ontvangen signalen om zelfs de kleinste wijziging in frequentie of sterkte op te vangen die wijzen op vloot manoeuvres van ver weg gelegen koninkrijken. Doordat het systeem zo complex is vereist elke scan een redelijke hoeveelheid deuterium om de benodigde energie op te wekken.',
    ],

    'jump_gate' => [
        'title'            => 'Sprongpoort',
        'description'      => 'Sprongpoorten zijn reusachtige transceivers die zelfs de grootste vloot in een oogwenk naar een verre sprongpoort kunnen sturen.',
        'description_long' => 'Een sprongpoort is een systeem bestaande uit reusachtige transmitters die in staat zijn zelfs de grootste vloten over te springen naar een ontvangende sprongpoort van dezelfde eigenaar waar dan ook in het universum zónder verlies van tijd.
Een sprongpoort verbruikt geen deuterium, maar na een sprong moet er een afkoelperiode zijn voor de volgende sprong kan plaatsvinden, anders raken de sprongpoorten oververhit. Het verzenden van grondstoffen via de sprongpoort is niet mogelijk. De afkoeltijd van de Sprong Poort wordt verminderd met elk upgradelevel, tot een maximum van level 9.',
    ],

    // -------------------------------------------------------------------------
    // Research objects (from ResearchObjects.php)
    // -------------------------------------------------------------------------

    'energy_technology' => [
        'title'            => 'Energietechniek',
        'description'      => 'De beheersing van verschillende soorten energie is noodzakelijk voor veel nieuwe technologieën.',
        'description_long' => 'Energietechniek houdt zich bezig met de kennis over en verfijning van energiebronnen, opslagoplossingen en technieken die voorzien in de basisbehoefte van vandaag: energie. 
Des te beter deze techniek ontwikkeld is, des te efficiënter zijn je systemen. Bepaalde niveaus zijn zelfs verplicht om bepaalde andere technieken te kunnen onderzoeken die afhankelijk zijn van kennis over energie.',
    ],

    'laser_technology' => [
        'title'            => 'Lasertechniek',
        'description'      => 'Het focussen van licht produceert een bundel die schade veroorzaakt wanneer deze een object raakt.',
        'description_long' => 'Laser (Light Amplification by Stimulated Emission of Radiaton) is een hoog energetische, monochrome straal van fotonen met uitstekende concentratiemogelijkheden. Lasers worden gebruikt in een uitgebreide reeks systemen: van optische computers tot zware laserwapens. Lasertechniek is belangrijke basis voor verder wapenonderzoek.',
    ],

    'ion_technology' => [
        'title'            => 'Iontechniek',
        'description'      => 'De concentratie van ionen maakt de bouw van kanonnen mogelijk die enorme schade kunnen aanrichten en de sloopkosten per niveau met 4% verlagen.',
        'description_long' => 'Ionen kunnen in een krachtige straal geconcentreerd worden. Deze ionstralen kunnen enorme schade aanrichten. Onze wetenschappers hebben het mogelijk gemaakt deze vernietigende kracht aan te wenden om de kosten van afbraak van gebouwen en systemen te reduceren. Voor elk level iontechniek nemen de afbraakkosten af met 4%.',
    ],

    'hyperspace_technology' => [
        'title'            => 'Hyperruimtetechniek',
        'description'      => 'Door de integratie van de 4e en 5e dimensie is het nu mogelijk een nieuw type aandrijving te onderzoeken dat zuiniger en efficiënter is.',
        'description_long' => 'Door de vierde en vijfde dimensie te gebruiken in voortstuwingstechniek kan een nieuw soort voortstuwingssysteem beschikbaar gemaakt worden - een welke efficiënter is en dus minder brandstof gebruikt dan de conventionele systemen. Daarbovenop zorgt hyperruimtetechniek voor de achtergrond voor hyperruimtereizen die gebruikt worden door grote oorlogsschepen en sprongpoorten. Het is een nieuwe en gecompliceerde techniek die een uitgebreid laboratorium en testfaciliteiten nodig heeft. Elke verbetering hiervan verhoogt de laadcapaciteit van jouw schepen met 5% van de basiswaarde.',
    ],

    'plasma_technology' => [
        'title'            => 'Plasmatechniek',
        'description'      => 'Een verdere ontwikkeling van de ionentechnologie die hoog-energetisch plasma versnelt, wat vervolgens verwoestende schade aanricht en bovendien de productie van metaal, kristal en deuterium optimaliseert (1%/0,66%/0,33% per niveau).',
        'description_long' => 'Verder onderzoek van iontechniek die geen ionen versneld, maar hoog energetisch plasma, veroorzaakt verwoestende schade bij de botsing met een voorwerp. Onze onderzoekers hebben ook ontdekt hoe ze hiermee de metaal- en kristalproductie kunnen verhogen.

De metaalproductie verhoogd met 1%, de kristalproductie met 0,66% en de deuteriumproductie met 0,33% per level plasmatechniek.',
    ],

    'combustion_drive' => [
        'title'            => 'Verbrandingsmotor',
        'description'      => 'De ontwikkeling van deze aandrijving maakt sommige schepen sneller, hoewel elk niveau de snelheid slechts met 10% van de basiswaarde verhoogt.',
        'description_long' => 'Verbrandingsmotoren behoren tot de oudste motoren die bestaan en zijn gebaseerd op afstoting. Deeltjes worden versneld en verlaten de motor, hierbij een kracht veroorzakend welke het schip in de andere richting verplaatst. De efficiëntie van deze verbrandingsmotoren is laag, maar ze zijn goedkoop te maken en hebben bewezen betrouwbaar te zijn. Hun grootte is relatief beperkt en ze vereisen geen enorme hoeveelheden computers tijdens het gebruik.
Onderzoek naar hogere niveaus zorgt voor steeds snellere motoren alhoewel elk niveau slechts voor een 10% verhoging in snelheid zorgt, gebaseerd op de basis snelheid van een gegeven schip.
Omdat deze techniek een van de basistechnieken is van een opkomend koninkrijk, moet je zo snel mogelijk onderzoek doen naar deze motoren. 
Voor: Grote Vrachtschepen, Recyclers, Spionagesondes en Lichte Gevechtschepen',
    ],

    'impulse_drive' => [
        'title'            => 'Impulsmotor',
        'description'      => 'De impulsaandrijving is gebaseerd op het reactieprincipe. Verdere ontwikkeling van deze aandrijving maakt sommige schepen sneller, hoewel elk niveau de snelheid slechts met 20% van de basiswaarde verhoogt.',
        'description_long' => 'Het impulsmotorsysteem is gebaseerd op het systeem van deeltjesuitstoting. De uitgestoten materie is afval dat gegenereerd wordt bij de fusiereactor die gebruikt wordt om de benodigde energie te leveren voor dit type voortstuwingssysteem. Daarnaast kunnen andere massa`s ook geïnjecteerd worden. Met elke level van de Impulsmotoren wordt de snelheid van bommenwerpers, kruisers, zware gevechtsschepen en kolonie schepen met 20% verhoogd ten opzichte van hun basiswaarde. Daarnaast worden kleine transporteurs uitgerust met Impulsmotors zodra het onderzoek level 5 bereikt heeft. Als het Impulsmotor onderzoek level 17 bereikt, worden Recylers ook uitgerust met Impulsmotors.

Interplanetaire raketten reizen ook verder met elk level.',
    ],

    'hyperspace_drive' => [
        'title'            => 'Hyperruimtemotor',
        'description'      => 'De hyperruimteaandrijving krult de ruimte om een schip heen. De ontwikkeling van deze aandrijving maakt sommige schepen sneller, hoewel elk niveau de snelheid slechts met 30% van de basiswaarde verhoogt.',
        'description_long' => 'Door de kromming van ruimtetijd in de onmiddellijke omgeving van het schip wordt ruimte zo sterk samengedrukt dat enorme afstanden razendsnel kunnen worden afgelegd. Hoe hoger het niveau van Hyperruimtemotoren, hoe sterker de samendrukking van de ruimte, en dus wordt elk schip (Interceptor, Slagschepen, Vernietiger, Ster des Doods, Navigators en Ruimers) sneller met 30% voor elk level. Daarnaast is de bommenwerper uitgerust met een Hyperruimtemotor zodra het onderzoek level 8 bereikt. Zodra het Hyperruimtemotoren onderzoek level 15 bereikt, is de Recycler uitgerust met een Hyperruimtemotor.',
    ],

    'espionage_technology' => [
        'title'            => 'Spionagetechniek',
        'description'      => 'Informatie over andere planeten en manen kan worden verkregen met behulp van deze technologie.',
        'description_long' => 'Spionagetechniek is het onderzoek naar sensoren, spionagemateriaal en kennis dat een koninkrijk nodig heeft om zich te beschermen tegen spionage-aanvallen van anderen en deze zelf uit te voeren. Des te geavanceerder deze techniek, des te meer gegevens verkregen kunnen worden van andere koninkrijken.
Voor spionagesondes en hun kans op succes is het verschil tussen hun niveau van spionagetechniek en dat van de tegenstander belangrijk. Des te hoger het eigen niveau, des te meer informatie kan een sonde verkrijgen en des te kleiner de kans dat de sonde gedetecteerd wordt door de strijdkrachten van de tegenstander. Des te meer sondes gestuurd worden, des te meer gegevens je terug krijgt - maar de kans om ontdekt te worden wordt ook groter.
Het niveau van spionagetechniek bepaalt eveneens de hoeveelheid details over een inkomende vloot: 
- Niveau 2 voegt het totaal aantal schepen toe aan de informatie over de vloot.
- Niveau 4 voegt vervolgens de types van de inkomende schepen toe.
- Niveau 8 voegt uiteindelijk gegevens over het type en het aantal inkomende schepen toe.
Spionagetechniek is erg belangrijk voor elk koninkrijk, of het nu aanvallend is of defensief. Men raadt aan om onderzoek in dit gebied te doen zodra je kleine vrachtschepen kunt produceren.',
    ],

    'computer_technology' => [
        'title'            => 'Computertechniek',
        'description'      => 'Meer vloten kunnen worden aangestuurd door computercapaciteiten te vergroten. Elk niveau van computertechnologie verhoogt het maximale aantal vloten met één.',
        'description_long' => 'Computertechniek wordt gebruikt voor steeds krachtigere gegevensverwerking en om controle-eenheden te maken. Elk niveau verhoogt de verwerkingskracht. Des hoger het niveau, des te meer vlootsloten je tegelijkertijd kunt gebruiken. Hoe meer vlootsloten een koninkrijk heeft, hoe meer activiteit er plaats kan vinden om meer inkomsten te genereren. Vlootsloten worden gebruikt voor militaire vloten, vrachttransport en spionagemanoeuvres. Het is een goed idee om constant onderzoek te doen op dit gebied om voldoende flexibiliteit aan je vloot te geven.',
    ],

    'astrophysics' => [
        'title'            => 'Astrofysica',
        'description'      => 'Met een astrofysica-onderzoeksmodule kunnen schepen lange expedities ondernemen. Elk tweede niveau van deze technologie stelt u in staat een extra planeet te koloniseren.',
        'description_long' => 'Verder onderzoek in de astrofysica stelt je in staat meer laboratoria te plaatsen op een schip. Dit maakt lange expedities in verre delen van het stelsel mogelijk.
Ook maakt verder onderzoek in de astrofysica het mogelijke meer te koloniseren. Voor iedere 2 levels van deze technologie kan een nieuwe planeet ontwikkeld worden.',
    ],

    'intergalactic_research_network' => [
        'title'            => 'Intergalactisch Onderzoeksnetwerk',
        'description'      => 'Onderzoekers op verschillende planeten communiceren via dit netwerk.',
        'description_long' => 'Onderzoekers van jouw planeten kunnen communiceren met elkaar via dit netwerk.Met elk level van dit onderzoek wordt je hoogste, niet-gelinkte onderzoekslaboratorium toegevoegd aan het netwerk. De levels van de onderzoekslabs worden bij elkaar opgeteld wanneer het netwerk is opgezet.Elk gelinkt onderzoekslaboratorium heeft het benodigde level van het geplande onderzoek nodig om mee te helpen met het gecombineerde onderzoek.',
    ],

    'graviton_technology' => [
        'title'            => 'Gravitontechniek',
        'description'      => 'Het afvuren van een geconcentreerde lading gravitondeeltjes kan een kunstmatig zwaartekrachtveld creëren dat schepen of zelfs manen kan vernietigen.',
        'description_long' => 'Een graviton is een elementair deeltje, verantwoordelijk voor zwaartekracht. Het heeft een rustmassa van nul en geen lading.
Door het afschieten van geconcentreerde gravitondeeltjes wordt een kunstmatig zwaartekrachtveld gegenereerd. De sterkte en aantrekkingskracht hiervan kan niet alleen schepen vernietigen maar zelfs complete manen. Om de benodigde hoeveelheid gravitondeeltjes te produceren dient de planeet in staat te zijn een enorme hoeveelheid energie te produceren. Graviton Onderzoek is vereist voor het bouwen van een destructieve Ster des Doods.',
    ],

    'weapon_technology' => [
        'title'            => 'Wapentechniek',
        'description'      => 'Wapenentechnologie maakt wapensystemen efficiënter. Elk niveau wapenentechnologie verhoogt de wapenkracht van eenheden met 10% van de basiswaarde.',
        'description_long' => 'Wapentechniek is onderzoek naar de ontwikkeling van bestaande wapensystemen. Het is hoofdzakelijk gefocust op het verhogen van de kracht en efficiëntie van wapens.
Door het verhogen van het niveau van deze techniek heeft hetzelfde wapen meer kracht en veroorzaakt meer schade - elk niveau verhoogt de kracht met 10% van de basiskracht van een wapen.
Het is bij wapentechniek belangrijk om bij te blijven met je vijanden, dus is het een goed idee om continu onderzoek te doen in dit gebied.',
    ],

    'shielding_technology' => [
        'title'            => 'Schildtechniek',
        'description'      => 'Schildtechnologie maakt de schilden op schepen en verdedigingsinstallaties efficiënter. Elk niveau schildtechnologie verhoogt de sterkte van de schilden met 10% van de basiswaarde.',
        'description_long' => 'Schildtechniek wordt gebruikt om een beschermend schild van deeltjes te bouwen om je structuren. Elk niveau verhoogt de effectieve bescherming met 10% (gebaseerd op het basisniveau van een gegeven schip of verdedigingswerk). Het niveau verhoogt in principe de hoeveelheid energie dat een schild kan opnemen voordat het instort. Schilden worden niet alleen gebruikt in schepen maar ook voor planetaire schildkoepels ter verdediging.',
    ],

    'armor_technology' => [
        'title'            => 'Pantsertechniek',
        'description'      => 'Speciale legeringen verbeteren het pantser op schepen en verdedigingsstructuren. De effectiviteit van het pantser kan per niveau met 10% worden verhoogd.',
        'description_long' => 'Gecompliceerde samenstellingen van legeringen zijn verantwoordelijk voor het constant verbeterende pantser van ruimteschepen en verdedigingswerken. Zodra een nieuwe legering heeft bewezen effectief te zijn, kan de moleculaire structuur van bestaande pantsers met behulp van straling veranderd worden. Zo maken al je ruimteschepen en verdedigingswerken altijd gebruik van het het laatst ontwikkelde pantser. Elk niveau van verbetering van pantsertechniek verhoogt de sterkte van rompbeplating met 10% van de basissterkte.',
    ],

    // ---- Civil Ships ----

    'small_cargo' => [
        'title'            => 'Klein vrachtschip',
        'description'      => 'Het kleine vrachtschip is een wendbaar schip dat snel grondstoffen naar andere planeten kan transporteren.',
        'description_long' => 'Transporteurs zijn ongeveer zo groot als gevechtsschepen, echter werd tijdens de constructie afgezien van motoren met hoge prestaties en wapenuitrusting ten gunste van vrachtcapaciteit. Met als resultaat dat een transporteur enkel en alleen ingezet kan worden waar gevochten wordt wanneer deze vergezeld wordt door gevechtsklare schepen.Zodra Impulsmotor-onderzoek level vijf bereikt zal de kleine transporteur reizen met verhoogde basissnelheid en voorzien worden van een Impulsmotor.',
    ],

    'large_cargo' => [
        'title'            => 'Groot vrachtschip',
        'description'      => 'Dit vrachtschip heeft een veel grotere laadcapaciteit dan het kleine vrachtschip, en is over het algemeen sneller dankzij een verbeterde aandrijving.',
        'description_long' => 'Dit type schip moet nooit alleen gestuurd worden, omdat het vrijwel geen wapens of andere technieken heeft zodat er zo veel mogelijk ruimte is voor lading. Het grote vrachtschip wordt gebruikt als een snelle bezorger van grondstoffen tussen planeten vanwege zijn geavanceerde verbrandingsmotor. Uiteraard vergezelt het vloten gedurende invallen in andere koninkrijken om zo veel mogelijk grondstoffen te stelen.',
    ],

    'colony_ship' => [
        'title'            => 'Kolonisatieschip',
        'description'      => 'Lege planeten kunnen worden gekoloniseerd met dit schip.',
        'description_long' => 'In de 20e eeuw besloot de mens naar de sterren te reiken. Als eerste doel werd een landing op de maan gesteld, daarna begon men aan het bouwen van ruimtestations. Niet lang daarna werd Mars gekoloniseerd. Al snel werd bepaald dat onze groei afhankelijk was van het koloniseren van andere werelden. Wetenschappers en ingenieurs van over de hele wereld kwamen samen om de grootste prestatie van de mensheid ooit te ontwikkelen. Het Kolonisatieschip was geboren. Dit schip wordt gebruikt om een pas ontdekte planeet klaar te maken voor kolonisatie. Zodra het zijn bestemming bereikt, wordt het schip onmiddellijk omgevormd tot bewoonbare woonruimte om te helpen bij het bevolken en ontginnen van de nieuwe wereld. Het maximale aantal planeten wordt daardoor bepaald door de voortgang in het onderzoek naar Astrofysica. Twee nieuwe niveaus van Astrofysica maken de kolonisatie van één extra planeet mogelijk.',
    ],

    'recycler' => [
        'title'            => 'Recycler',
        'description'      => 'Recyclers zijn de enige schepen die puinvelden kunnen oogsten die na gevechten in een baan om een planeet drijven.',
        'description_long' => 'De strijd in de ruimte heeft nóg grotere proporties aangenomen. Duizenden schepen zijn vernietigd en de grondstoffen van hun wrakken lijken voor eeuwig verloren te zijn gegaan in de puinvelden. Normale vrachtschepen konden niet dicht genoeg bij deze velden in de buurt komen zonder substantiële schade te riskeren.
Een recente ontwikkeling in schildtechnologie heeft dit probleem efficiënt weten te omzeilen. Er werd onlangs een nieuwe scheepsklasse gebouwd die sterk lijkt op de Transporteurs: de Recyclers. Hun inspanningen hielpen bij het verzamelen van de verloren gewaande grondstoffen en deze te bergen. Het puin vormde niet langer meer een serieus gevaar dankzij de nieuwe schilden.

Zodra Impulsmotor-onderzoek level 17 bereikt heeft zullen Recyclers uitgerust worden met Impulsmotoren. Zodra Hyperruimtemotor-onderzoek level 15 bereikt heeft zullen Recyclers uitgerust worden met Hyperruimtemotoren.',
    ],

    'espionage_probe' => [
        'title'            => 'Spionagesonde',
        'description'      => 'Spionagesondes zijn kleine, wendbare drones die gegevens verstrekken over vloten en planeten over grote afstanden.',
        'description_long' => 'Spionagesondes zijn kleine onbemande robots met een enorm snel voortstuwingssysteem die worden gebruikt om planeten van andere koninkrijken te bespioneren. Met hun erg geavanceerde communicatiesysteem kunnen deze sondes gegevens over grote afstanden versturen.  Zodra ze gearriveerd zijn in een baan om hun doelwit blijven de sondes daar om gegevens te verzamelen en gedurende die periode zijn ze vrij eenvoudig te detecteren. Vanwege grootte en gevechtrestricties hebben de sondes geen bepantsering of schilden en ook geen wapens. Zodra ze gedetecteerd worden, worden sondes meestal vernietigd door hun zwakke constructie.',
    ],

    'solar_satellite' => [
        'title'            => 'Zonne-energiesatelliet',
        'description'      => 'Zonnesatellieten zijn eenvoudige platforms van zonnecellen, gelegen in een hoge, stationaire baan. Ze vangen zonlicht op en sturen het via laser naar het grondstation.',
        'description_long' => 'Zonne-energiesatellieten zijn eenvoudige satellieten die fotovoltaïsche cellen bevatten en een manier om energie naar de planeet te sturen. De energie wordt simpelweg naar de planeet gestuurd met een speciale laser. De efficiëntie van deze apparaten is gerelateerd aan de hoeveelheid zonlicht, wat ze meer of minder efficiënt maakt, afhankelijk van de afstand van de planeet tot de zon.
Door hun lage prijs en simpele installatie worden deze satellieten veel gebruikt om in de energie behoefte te voorzien.
Helaas worden deze satellieten tijdens aanvallen in groten getale vernietigd.',
    ],

    'crawler' => [
        'title'            => 'Processer',
        'description'      => 'Crawlers verhogen de productie van metaal, kristal en deuterium op hun toegewezen planeet met respectievelijk 0,02%, 0,02% en 0,02%. Als verzamelaar neemt de productie ook toe. Het maximale totale bonusbedrag hangt af van het algehele niveau van uw mijnen.',
        'description_long' => 'De Processer is een groot graafvoertuig die de productie van mijnen en fabrieken verhoogd. Het is wendbaarder dan het eruit ziet maar niet echt robuust. Elke Processer verhoogd de metaalproductie met 0,02%, de kristalproductie met 0,02% en de deuteriumproductie met 0,02%. De productie verhoogd als een verzamelaar. De maximale totale bonus hangt af van het globale level van je mijnen.',
    ],

    'pathfinder' => [
        'title'            => 'Navigator',
        'description'      => 'De pionier is een snel en wendbaar schip, speciaal gebouwd voor expedities in onbekende ruimtesectoren.',
        'description_long' => 'Navigators zijn snel en ruim. Hun bouwwijze is geoptimaliseerd om onbekende territoria te betreden. Ze zijn in staat om puinvelden te ontdekken en ruimen tijdens expedities. Daarnaast kunnen ze voorwerpen vinden op expedities. Totale opbrengst neemt ook toe.',
    ],

    // ---- Military Ships ----

    'light_fighter' => [
        'title'            => 'Licht gevechtsschip',
        'description'      => 'Dit is het eerste gevechtsschip dat alle keizers zullen bouwen. De lichte jager is een wendbaar schip, maar kwetsbaar op zichzelf. In grote aantallen kunnen ze een grote bedreiging vormen voor elk imperium. Ze zijn de eersten om kleine en grote vrachtschepen te vergezellen naar vijandige planeten met geringe verdediging.',
        'description_long' => 'Gezien de lichte bepantsering en slechte wapensystemen, behoren lichte gevechtsschepen tot de ondersteunende schepen in een gevecht. Hun wendbaarheid en snelheid, gekoppeld met de aantallen waarin ze vaak voorkomen zorgt voor een schildachtige buffer voor grotere schepen die niet zo wendbaar zijn.',
    ],

    'heavy_fighter' => [
        'title'            => 'Zwaar gevechtsschip',
        'description'      => 'Deze jager is beter bepantserd en heeft een hogere aanvalskracht dan de lichte jager.',
        'description_long' => 'Gedurende de verbetering van het lichte gevechtsschip kwamen de onderzoekers op een punt waar de conventionele motoren hun limiet bereikten. Om de wendbaarheid van het nieuwe gevechtsschip mogelijk te maken werd voor het eerst een impulsmotor gebruikt. Ondanks de hogere kosten en complexiteit werden nieuwe technieken mogelijk, gedeeltelijk door het gebruik van duurdere materialen in het algemeen. Door het gebruik van impulstechniek kwam er meer energie beschikbaar voor wapen systemen en schilden. Verbeterde structurele integriteit en meer vuurkracht maken dit schip een veel groter gevaar in een gevecht dan zijn voorloper.',
    ],

    'cruiser' => [
        'title'            => 'Kruiser',
        'description'      => 'Kruisers zijn bijna driemaal zo zwaar bepantserd als zware jagers en hebben meer dan het dubbele vuurvermogen. Bovendien zijn ze zeer snel.',
        'description_long' => 'Met de komst van zware lasers en ionkanonen op het slagveld werden de gevechtsschepen meer en meer onder druk gezet. Ondanks veel wijzigingen konden de vuurkracht en rompbeplating niet ver genoeg verbeterd worden om weerstand te bieden aan deze nieuwe verdedigingssystemen. 
Dit is de reden geweest om een compleet nieuw type schip te ontwikkelen met meer rompbeplating en sterkere wapens. Zo werd de gevechtskruiser geboren. De nieuwe schepen hebben een bepantsering die bijna drie keer zo sterk is als die van zware gevechtsschepen en ze hebben meer dan twee keer zoveel vuurkracht. Hun reissnelheid valt ook onder de snelste ooit gezien. Er is vrijwel geen beter schip om te gebruiken tegen lichte en middelmatige verdedigingswerken op een planeet en dat is de reden waarom kruisers wijd verspreid zijn door het hele universum gedurende een periode van bijna honderd jaar.
Helaas eindigde de overheersing van dit type schip snel met de beschikbaarheid van nieuwe en sterkere verdedigings systemen zoals gausskanonnen en plasmakanonnen. Tegenwoordig worden ze nog steeds gebruikt om te vechten tegen grote aantallen gevechtsschepen vanwege hun effectieve wapen systemen tegen dit type tegenstander.',
    ],

    'battle_ship' => [
        'title'            => 'Slagschip',
        'description'      => 'Slagschepen vormen de ruggengraat van een vloot. Hun zware kanonnen, hoge snelheid en grote vrachtruimen maken hen tot tegenstanders die serieus genomen moeten worden.',
        'description_long' => 'Slagschepen verzorgen de hoofdmoot van elke militaire vloot. Zwaar bepantserd, sterke wapens en hoge kruissnelheid maken dit schip onmisbaar voor elk koninkrijk. Daar komt nog bij dat de grote opslagruimte erg geschikt is voor invallen.',
    ],

    'battlecruiser' => [
        'title'            => 'Interceptor',
        'description'      => 'De slagkruiser is sterk gespecialiseerd in het onderscheppen van vijandige vloten.',
        'description_long' => 'Toen de thuiswerelden van de ruimtekolonisten zwaar te lijden kregen onder massale aanvallen van vijandige vloten kwamen ingenieurs en geleerden met het plan om een nieuw schip te ontwerpen, dat als primaire taak had aanvallende vloten af te slaan. Echter door de traagheid van het bureaucratische apparaat verdwenen de plannen voor dit defensieve schip in de koelkast. Het was echter ten tijde van de 3de intergalactische oorlog (3713-3744 O.T.) dat in een versneld tempo verder werd gewerkt aan de ontwikkeling van dit nieuwe schip. Het resultaat is een hoogtechnologisch schip dat uitgerust is met verfijnde radarsystemen en wapensystemen die het mogelijk maken om zeer effectief aanvallen uit te voeren tegen andere schepen. De Interceptor is dan misschien niet het snelste schip uit de vloot, maar in combinatie met de verdediging op de planeet kan het ongeziene schade veroorzaken bij de aanvallende vloten. Onder de piloten kreeg de interceptor al snel de bijnaam `de Colibri`, omdat het schip een hoge vuursnelheid heeft, als de vleugelslagen van een colibri.',
    ],

    'bomber' => [
        'title'            => 'Bommenwerper',
        'description'      => 'De bommenwerper werd speciaal ontwikkeld om de planetaire verdedigingen van een wereld te vernietigen.',
        'description_long' => 'De bommenwerper is een ruimteschip speciaal ontwikkeld om door zware verdediging te breken. Door een laser geleid richtsysteem kunnen plasmabommen precies boven hun doelwit losgelaten worden, wat enorme vernietiging veroorzaakt in verdedigingssystemen.De basis snelheid van je bommenwerpers wordt verhoogd wanneer niveau 8 van de hyperruimtemotor is onderzocht, aangezien ze dan worden uitgerust met hyperruimtemotoren.',
    ],

    'destroyer' => [
        'title'            => 'Vernietiger',
        'description'      => 'De vernietiger is de koning van de oorlogsschepen.',
        'description_long' => 'Met de vernietiger betreedt de moeder van alle gevechtsschepen de arena. Haar multi-phalanx wapensysteem bestaat uit ion-, plasma- en gausskanonnen die gemonteerd zijn op snel reagerende torens wat ze toestaat om gevechtsschepen met een kans van 99% te vernietigen. De grootte van het schip is ook zijn nadeel omdat de manoeuvreerbaarheid gelimiteerd is, wat de vernietiger meer een gevechtsstation maakt dan een gevechtsschip. De brandstofconsumptie van dit enorme schip is ongeveer net zo groot als zijn vuurkracht...',
    ],

    'deathstar' => [
        'title'            => 'Ster des Doods',
        'description'      => 'De destructieve kracht van de ster des doods is ongeëvenaard.',
        'description_long' => 'Een ster des doods is uitgerust met een enorm gravitonkanon, dat vrijwel alles met een enkel schot vernietigt, of het nu vernietigers zijn of manen. Om de benodigde energie op te wekken bestaat de binnenkant van een ster des doods vrijwel compleet uit energiecentrales. De grootte van de ster des doods limiteert ook zijn snelheid, welke enorm laag is. Men zegt dat de kapitein vaak helpt duwen om hem sneller voort te bewegen. Alleen enorme en geavanceerde koninkrijken hebben de mankracht en uitgebreide kennis om zulke ruimteschepen met de grootte van een maan te creëren.',
    ],

    'reaper' => [
        'title'            => 'Ruimer',
        'description'      => 'De maaier is een krachtig gevechtsschip gespecialiseerd in agressieve aanvallen en het oogsten van puinvelden.',
        'description_long' => 'Er is niks dodelijker dan een schip van de Ruimerklasse. Deze schepen combineren vuurkracht, machtige schilden, snelheid en capaciteit, samen met het unieke vermogen om een deel van de gemaakte puinvelden direct na een gevecht te ruimen. Deze vaardigheid werkt echter niet in gevechten tegen piraten en aliens.',
    ],

    // ---- Defense ----

    'rocket_launcher' => [
        'title'            => 'Raketlanceerder',
        'description'      => 'De raketlanceerder is een eenvoudige, kosteneffectieve verdedigingsoptie.',
        'description_long' => 'De raketlanceerder is een eenvoudig maar enorm waardevol verdedigingssysteem. Het wordt behoorlijk effectief in grote hoeveelheden en kan gebouwd worden zonder specifiek onderzoek omdat het een eenvoudig ballistisch wapen is. De lage productie kosten maken het effectief tegen kleine vloten maar behoudt effect zelfs wanneer zwaardere verdedigingssystemen beschikbaar zijn. Later worden ze gebruikt als schroot in gevechten. In het algemeen geldt dat verdedigingssystemen zichzelf deactiveren wanneer kritieke waarden bereikt worden om een kans op reparatie te verkrijgen. Gemiddeld genomen heeft een verdedigingswerk 70% kans om gerepareerd te worden na een gevecht.',
    ],

    'light_laser' => [
        'title'            => 'Kleine laser',
        'description'      => 'Geconcentreerd vuren op een doel met fotonen kan aanzienlijk meer schade produceren dan standaard ballistieke wapens.',
        'description_long' => 'Om de steeds sneller gaande ontwikkeling van ruimtevaarttechniek bij te houden moesten wetenschappers een nieuw verdedigingssysteem bedenken wat sterkere en beter uitgeruste schepen aan kon.Al snel werd de kleine laser geboren, welke in staat is om een enorm geconcentreerde laserstraal af te vuren op het doelwit, wat veel meer schade doet dan de conventionele raketten. Ook de schilden zijn verbeterd om af te rekenen met de grotere vuurkracht van moderne schepen. Omdat de lage prijs een essentieel onderdeel was van de ontwikkeling, is de basisstructuur niet verbeterd in vergelijking met de raketlanceerder.Omdat de kleine laser de meeste vuurkracht biedt voor zijn geld is het het best bekende verdedigingsysteem, wat gebruikt wordt door kleine opkomende koninkrijken en grote koninkrijken die zich uitspreiden over meerdere melkwegen.',
    ],

    'heavy_laser' => [
        'title'            => 'Grote laser',
        'description'      => 'De zware laser is de logische ontwikkeling van de lichte laser.',
        'description_long' => 'De grote laser is een directe afstammeling van het kleine laser systeem. De structurele integriteit is verbeterd en nieuwe materialen zijn gebruikt. Op deze manier kon de bepantsering verbeterd worden, en met de nieuwe energie en computer systemen kan veel meer energie afgevuurd worden op een doelwit dan met een kleine laser.',
    ],

    'gauss_cannon' => [
        'title'            => 'Gausskanon',
        'description'      => 'Het gausskanon vuurt projectielen van tonnen zwaar af met hoge snelheden.',
        'description_long' => 'Projectielwapens werden geacht gedateerd te zijn, gegeven de moderne kernfusietechniek, nieuwe energiebronnen, de ontdekking van hyperruimtetechniek en verder verbeterde legeringen. Het was echter dezelfde energie techniek die eens zijn plaats innam die nu weer gebruikt wordt om verder te gaan, naar de volgende eeuw: het onderliggende principe is al lang bekend en dateert uit de 20e en 21e eeuw: de deeltjesversneller.Een gausskanon is in feite niets meer dan een enorm uitvergrote deeltjesversneller waarin projectielen met een gewicht van meerdere tonnen versneld worden, gebruik makend van enorme electromagnetische spoelen. De uitgangssnelheid van deze projectielen is zo hoog dat stof in de lucht verbrand wordt en de terugslag een kleine aardbeving veroorzaakt .Zelfs de nieuwste pantserlegeringen en schildtechnieken hebben moeite om de inslag van zo`n projectiel te weerstaan - vaker wel dan niet vliegt het projectiel dwars door zijn doelwit heen.',
    ],

    'ion_cannon' => [
        'title'            => 'Ionkanon',
        'description'      => 'Het ionenkanon vuurt een continue bundel versnellende ionen af die aanzienlijke schade veroorzaken aan objecten die het raakt.',
        'description_long' => 'In de 21e eeuw was er een techniek bekend als EMP, wat staat voor ElectroMagnetische Puls. Zo`n energiepuls is vooral gevaarlijk voor systemen die elektriciteit gebruiken of er gevoelig voor zijn. In die tijd werden zulke wapens gebruikt in bommen of raketten, maar met de verder gaande ontwikkeling van EMP is het nu mogelijk om zulke eenheden te monteren op simpele kanonnen. Het ionkanon is  op dit moment het best uitgerust van dit type wapens. De nauwe straal van ionen vernietigt elk stuk elektronica dat niet afgeschermd is in het doelwit en destabiliseert het schild circuit van het ruimteschip. Deze combinatie zorgt meestal voor complete vernietiging ondanks het feit dat levende wezens niet direct gevaar lopen.De enige ruimteschepen die ionkanonnen hebben zijn de kruiser en de vernietiger, vanwege de hoge energiebehoefte van deze wapens en het feit dat gevechten over het algemeen vernietiging van het doelwit vereisen, niet verlamming.',
    ],

    'plasma_turret' => [
        'title'            => 'Plasmakanon',
        'description'      => 'Plasmakanonnen lossen de energie van een zonnevlam en overtreffen zelfs de vernietiger in destructief effect.',
        'description_long' => 'De lasertechniek is bijna geperfectioneerd, de iontechniek leek perfect en in het algemeen leek er geen manier te zijn om bestaande wapensystemen te verbeteren. Maar dit veranderde toen men het idee kreeg om deze twee technieken samen te voegen. De laser wordt gebruikt om deuteriumdeeltjes op te warmen tot miljoenen graden, iontechniek wordt dan gebruikt om deze hete deeltjes elektrisch te laden en elektromagnetische kennis wordt vervolgens gebruikt om dit gevaarlijke plasma bij elkaar te houden.De gloeiende plasmastraal ziet er erg mooi uit wanneer deze op weg is naar een doelwit, maar voor een bemanning van een ruimteschip betekent deze mooie plasmastraal dodelijke vernietiging. Plasmawapens zijn een van de gevaarlijkste wapens, maar ze hebben hun prijs.',
    ],

    'small_shield_dome' => [
        'title'            => 'Kleine planetaire schildkoepel',
        'description'      => 'De kleine schildkoepel bedekt een hele planeet met een veld dat een enorme hoeveelheid energie kan absorberen.',
        'description_long' => 'Lang voordat schildgenerators geïntegreerd en draagbaar werden, waren er grote oude generators aan de oppervlakte van planeten. Deze waren in staat om een enorme beschermende koepel om de oppervlakte van een hele planeet te maken en waren in staat om enorme hoeveelheden vuurkracht te absorberen. Af en toe wordt een klein gevechtskonvooi verslagen door een van deze koepels. Door gebruik te maken van meer geavanceerde schildtechniek kunnen deze koepels verder verbeterd worden zodat ze nog meer energie kunnen absorberen.Er kan uiteraard maar een kleine koepel per planeet aanwezig zijn.',
    ],

    'large_shield_dome' => [
        'title'            => 'Grote planetaire schildkoepel',
        'description'      => 'De evolutie van de kleine schildkoepel kan aanzienlijk meer energie aanwenden om aanvallen te weerstaan.',
        'description_long' => 'Dit is een geavanceerde versie van de schildkoepel en zijn belangrijkste eigenschap is de verbeterde capaciteit om energie te absorberen. Het is gebaseerd op dezelfde techniek als de kleine schildkoepel. De generatoren zijn ook stiller in gebruik.',
    ],

    'anti_ballistic_missile' => [
        'title'            => 'Anti-ballistische raketten',
        'description'      => 'Anti-ballistische raketten vernietigen aanvallende interplanetaire raketten.',
        'description_long' => 'Anti-ballistische raketten vernietigen aanvallende raketten. Elke anti-ballistische raket vernietigt een aankomende interplanetaire raket.
Wanneer een aankomende interplanetaire raketaanval wordt gezien, zal automatisch door de computers een onderscheppingsbaan worden berekend. Gedurende de vlucht is de anti-ballistische raket doorlopend aan het bijsturen tot hij de ipr bereikt en vernietigt.
Elke anti-ballistische raket vernietigt één interplanetaire raket.',
    ],

    'interplanetary_missile' => [
        'title'            => 'Interplanetaire raketten',
        'description'      => 'Interplanetaire raketten vernietigen vijandige verdedigingen.',
        'description_long' => 'Interplanetaire raketten vernietigen de verdediging van een doelwit. Verdedigingswerken vernietigd door een interplanetaire raket kunnen niet gerepareerd worden.
De enige manier om je te verdedigen tegen interplanetaire raketten is via anti-ballistische raketten.',
    ],

    // ---- Shop Booster Items ----

    'kraken' => [
        'title'       => 'KRAKEN',
        'description' => 'Verlaagt de bouwtijd van gebouwen die momenteel in aanbouw zijn met <b>:duration</b>.',
    ],

    'detroid' => [
        'title'       => 'DETROID',
        'description' => 'Verlaagt de constructietijd van huidige scheepswerf-contracten met <b>:duration</b>.',
    ],

    'newtron' => [
        'title'       => 'NEWTRON',
        'description' => 'Verlaagt de onderzoekstijd voor alle onderzoeken die momenteel bezig zijn met <b>:duration</b>.',
    ],
];
