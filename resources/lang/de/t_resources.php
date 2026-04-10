<?php

return [
    'metal_mine' => [
        'title'            => 'Metallmine',
        'description'      => 'Für die Gewinnung von Metallerz sind Metallminen von grundlegender Bedeutung für jedes aufstrebende und etablierte Imperium.',
        'description_long' => 'Metall ist der Primärrohstoff zum Aufbau eines Imperiums. In größeren Tiefen können die Minen mehr verwertbares Metall für den Bau von Gebäuden, Schiffen, Verteidigungsanlagen und Forschung produzieren. Je tiefer die Minen graben, desto mehr Energie wird für die maximale Produktion benötigt. Da Metall der am häufigsten vorkommende Rohstoff ist, gilt sein Handelswert als der niedrigste aller Rohstoffe.',
    ],

    'crystal_mine' => [
        'title'            => 'Kristallmine',
        'description'      => 'Kristalle sind der Hauptrohstoff für den Bau elektronischer Schaltkreise und bestimmter Legierungsverbindungen.',
        'description_long' => 'Kristallminen liefern den Hauptrohstoff für die Herstellung elektronischer Schaltkreise und bestimmter Legierungsverbindungen. Der Abbau von Kristall verbraucht etwa eineinhalb Mal mehr Energie als der Metallabbau, was Kristall wertvoller macht. Fast alle Schiffe und alle Gebäude benötigen Kristall. Die meisten für den Schiffsbau benötigten Kristalle sind jedoch sehr selten und können wie Metall nur in bestimmten Tiefen gefunden werden. Der Ausbau der Minen in tiefere Schichten erhöht daher die produzierte Kristallmenge.',
    ],

    'deuterium_synthesizer' => [
        'title'            => 'Deuterium-Synthetisierer',
        'description'      => 'Deuterium-Synthetisierer gewinnen das in Spuren vorhandene Deuterium aus dem Wasser eines Planeten.',
        'description_long' => 'Deuterium wird auch schwerer Wasserstoff genannt. Es ist ein stabiles Isotop des Wasserstoffs mit einem natürlichen Vorkommen in den Ozeanen der Kolonien von ungefähr einem Atom auf 6500 Wasserstoffatome (~154 PPM). Deuterium macht somit etwa 0,015% (gewichtsbezogen 0,030%) des gesamten Wasserstoffs aus. Deuterium wird von speziellen Synthetisierern verarbeitet, die das Wasser mithilfe speziell entwickelter Zentrifugen vom Deuterium trennen können. Der Ausbau des Synthetisierers ermöglicht die Verarbeitung größerer Deuteriumvorkommen. Deuterium wird für Sensorphalanx-Scans, Galaxieansichten, als Treibstoff für Schiffe und für spezialisierte Forschungsvorhaben verwendet.',
    ],

    'solar_plant' => [
        'title'            => 'Solarkraftwerk',
        'description'      => 'Solarkraftwerke absorbieren Energie aus der Sonnenstrahlung. Alle Minen benötigen Energie zum Betrieb.',
        'description_long' => 'Riesige Solaranlagen werden zur Energiegewinnung für die Minen und den Deuterium-Synthetisierer eingesetzt. Beim Ausbau des Solarkraftwerks vergrößert sich die Oberfläche der Photovoltaikzellen auf dem Planeten, was zu einer höheren Energieausbeute über die Stromnetze des Planeten führt.',
    ],

    'fusion_plant' => [
        'title'            => 'Fusionskraftwerk',
        'description'      => 'Das Fusionskraftwerk nutzt Deuterium zur Energiegewinnung.',
        'description_long' => 'In Fusionskraftwerken werden Wasserstoffkerne unter enormem Druck und hoher Temperatur zu Heliumkernen verschmolzen, wobei gewaltige Mengen an Energie freigesetzt werden. Pro Gramm verbrauchtem Deuterium können bis zu 41,32*10^-13 Joule Energie erzeugt werden; mit 1 g lassen sich 172 MWh Energie produzieren.

Größere Reaktorkomplexe verbrauchen mehr Deuterium und können pro Stunde mehr Energie erzeugen. Der Energieeffekt kann durch die Erforschung der Energietechnik gesteigert werden.

Die Energieproduktion des Fusionskraftwerks wird wie folgt berechnet:
30 * [Stufe Fusionskraftwerk] * (1,05 + [Stufe Energietechnik] * 0,01) ^ [Stufe Fusionskraftwerk]',
    ],

    'metal_store' => [
        'title'            => 'Metallspeicher',
        'description'      => 'Bietet Lagerkapazität für überschüssiges Metall.',
        'description_long' => 'Diese riesige Lagereinrichtung dient zur Aufbewahrung von Metallerz. Jede Ausbaustufe erhöht die Menge an Metallerz, die gelagert werden kann. Sind die Speicher voll, wird kein weiteres Metall mehr abgebaut.

Der Metallspeicher schützt einen bestimmten Prozentsatz der täglichen Minenproduktion (max. 10 Prozent).',
    ],

    'crystal_store' => [
        'title'            => 'Kristallspeicher',
        'description'      => 'Bietet Lagerkapazität für überschüssiges Kristall.',
        'description_long' => 'Das unverarbeitete Kristall wird zwischenzeitlich in diesen riesigen Lagerhallen aufbewahrt. Mit jeder Ausbaustufe erhöht sich die Menge an Kristall, die gelagert werden kann. Sind die Kristallspeicher voll, wird kein weiteres Kristall mehr abgebaut.

Der Kristallspeicher schützt einen bestimmten Prozentsatz der täglichen Minenproduktion (max. 10 Prozent).',
    ],

    'deuterium_store' => [
        'title'            => 'Deuteriumtank',
        'description'      => 'Riesige Tanks zur Lagerung von neu gewonnenem Deuterium.',
        'description_long' => 'Der Deuteriumtank dient zur Lagerung von neu synthetisiertem Deuterium. Nach der Verarbeitung durch den Synthetisierer wird es zur späteren Verwendung in diesen Tank geleitet. Mit jeder Ausbaustufe des Tanks wird die Gesamtlagerkapazität erhöht. Ist die Kapazität erreicht, wird kein weiteres Deuterium mehr synthetisiert.

Der Deuteriumtank schützt einen bestimmten Prozentsatz der täglichen Produktion des Synthetisierers (max. 10 Prozent).',
    ],

    // -------------------------------------------------------------------------
    // Station / Facilities objects (from StationObjects.php)
    // -------------------------------------------------------------------------

    'robot_factory' => [
        'title'            => 'Roboterfabrik',
        'description'      => 'Die Roboterfabrik stellt Bauroboter zur Verfügung, die den Gebäudebau unterstützen. Jede Stufe erhöht die Ausbaugeschwindigkeit der Gebäude.',
        'description_long' => 'Das Hauptziel der Roboterfabrik ist die Produktion modernster Bauroboter. Jeder Ausbau der Roboterfabrik führt zur Produktion schnellerer Roboter, die dazu dienen, die Bauzeit von Gebäuden zu verkürzen.',
    ],

    'shipyard' => [
        'title'            => 'Raumschiffswerft',
        'description'      => 'In der planetaren Raumschiffswerft werden alle Typen von Schiffen und Verteidigungsanlagen gebaut.',
        'description_long' => 'Die planetare Raumschiffswerft ist verantwortlich für den Bau von Raumschiffen und Verteidigungsmechanismen. Beim Ausbau der Werft kann eine größere Vielfalt an Fahrzeugen mit deutlich höherer Geschwindigkeit produziert werden. Ist eine Nanitenfabrik auf dem Planeten vorhanden, wird die Geschwindigkeit, mit der Schiffe gebaut werden, massiv erhöht.',
    ],

    'research_lab' => [
        'title'            => 'Forschungslabor',
        'description'      => 'Ein Forschungslabor wird benötigt, um neue Technologien zu erforschen.',
        'description_long' => 'Ein wesentlicher Bestandteil jedes Imperiums: Im Forschungslabor werden neue Technologien entdeckt und bestehende verbessert. Mit jeder Ausbaustufe des Forschungslabors wird die Geschwindigkeit, mit der neue Technologien erforscht werden, erhöht und gleichzeitig werden neuere Technologien zur Erforschung freigeschaltet. Um Forschung so schnell wie möglich durchzuführen, werden Wissenschaftler sofort zur Kolonie entsandt, um mit der Arbeit und Entwicklung zu beginnen. Auf diese Weise kann Wissen über neue Technologien leicht im ganzen Imperium verbreitet werden.',
    ],

    'alliance_depot' => [
        'title'            => 'Allianzdepot',
        'description'      => 'Das Allianzdepot versorgt verbündete Flotten im Orbit mit Treibstoff, die bei der Verteidigung helfen.',
        'description_long' => 'Das Allianzdepot versorgt verbündete Flotten im Orbit mit Treibstoff, die bei der Verteidigung helfen. Mit jeder Ausbaustufe des Allianzdepots kann ein bestimmter Deuteriumbedarf pro Stunde an eine im Orbit befindliche Flotte gesendet werden.',
    ],

    'missile_silo' => [
        'title'            => 'Raketensilo',
        'description'      => 'Raketensilos dienen zur Lagerung von Raketen.',
        'description_long' => 'Raketensilos werden zum Bau, zur Lagerung und zum Abschuss von Interplanetarraketen und Abfangraketen verwendet. Mit jeder Ausbaustufe des Silos können fünf Interplanetarraketen oder zehn Abfangraketen gelagert werden. Eine Interplanetarrakete benötigt denselben Platz wie zwei Abfangraketen. Die Lagerung von sowohl Interplanetarraketen als auch Abfangraketen im selben Silo ist erlaubt.',
    ],

    'nano_factory' => [
        'title'            => 'Nanitenfabrik',
        'description'      => 'Dies ist das Nonplusultra der Robotertechnologie. Jede Stufe halbiert die Bauzeit für Gebäude, Schiffe und Verteidigungsanlagen.',
        'description_long' => 'Eine Nanomaschine, auch Nanit genannt, ist ein mechanisches oder elektromechanisches Gerät, dessen Abmessungen in Nanometern (Millionstelmillimeter oder Einheiten von 10^-9 Meter) gemessen werden. Die mikroskopische Größe der Nanomaschinen bedeutet eine höhere Betriebsgeschwindigkeit. Diese Fabrik produziert Nanomaschinen, die die ultimative Entwicklung in der Robotertechnologie darstellen. Nach dem Bau verringert jeder Ausbau die Produktionszeit für Gebäude, Schiffe und Verteidigungsanlagen erheblich.',
    ],

    'terraformer' => [
        'title'            => 'Terraformer',
        'description'      => 'Der Terraformer vergrößert die nutzbare Oberfläche von Planeten.',
        'description_long' => 'Mit der zunehmenden Bebauung der Planeten wird auch der Wohnraum für die Kolonie immer knapper. Herkömmliche Methoden wie Hochhaus- und Untertagebau reichen zunehmend nicht mehr aus. Eine kleine Gruppe von Hochenergiephysikern und Nanoingenieuren fand schließlich die Lösung: Terraforming.
Unter Einsatz gewaltiger Energiemengen kann der Terraformer ganze Landstriche oder sogar Kontinente nutzbar machen. In diesem Gebäude werden speziell für diesen Zweck geschaffene Naniten produziert, die eine gleichbleibende Bodenqualität sicherstellen.

Jede Terraformerstufe ermöglicht die Kultivierung von 5 Feldern. Mit jeder Stufe belegt der Terraformer selbst ein Feld. Alle 2 Terraformerstufen erhält man 1 Bonusfeld.

Einmal gebaut, kann der Terraformer nicht mehr abgerissen werden.',
    ],

    'space_dock' => [
        'title'            => 'Raumdock',
        'description'      => 'Im Raumdock können Trümmerteile repariert werden.',
        'description_long' => 'Das Raumdock bietet die Möglichkeit, im Kampf zerstörte Schiffe zu reparieren, die Trümmer hinterlassen haben. Die Reparaturzeit beträgt maximal 12 Stunden, es dauert aber mindestens 30 Minuten, bis die Schiffe wieder in Dienst gestellt werden können.

Reparaturen müssen innerhalb von 3 Tagen nach Entstehung der Trümmer begonnen werden. Die reparierten Schiffe müssen nach Abschluss der Reparaturen manuell wieder in Dienst gestellt werden. Geschieht dies nicht, werden einzelne Schiffe jedes Typs nach 3 Tagen automatisch in Dienst gestellt.

Trümmer entstehen nur, wenn mehr als 150.000 Einheiten vernichtet wurden, einschließlich eigener Schiffe, die am Kampf teilgenommen haben, mit einem Wert von mindestens 5% der Schiffspunkte.

Da das Raumdock im Orbit schwebt, benötigt es kein Planetenfeld.',
    ],

    'lunar_base' => [
        'title'            => 'Mondbasis',
        'description'      => 'Da der Mond keine Atmosphäre hat, wird eine Mondbasis benötigt, um bewohnbaren Raum zu schaffen.',
        'description_long' => 'Ein Mond hat keine Atmosphäre, daher muss zunächst eine Mondbasis errichtet werden, bevor eine Siedlung aufgebaut werden kann. Diese stellt dann Sauerstoff, Heizung und Schwerkraft bereit. Mit jeder errichteten Stufe wird ein größerer Lebens- und Entwicklungsbereich innerhalb der Biosphäre zur Verfügung gestellt. Jede gebaute Stufe ermöglicht drei Felder für weitere Gebäude. Mit jeder Stufe belegt die Mondbasis selbst ein Feld.
Einmal gebaut, kann die Mondbasis nicht mehr abgerissen werden.',
    ],

    'sensor_phalanx' => [
        'title'            => 'Sensorphalanx',
        'description'      => 'Mit der Sensorphalanx können Flotten anderer Imperien entdeckt und beobachtet werden. Je größer die Sensorphalanx, desto größer die Reichweite.',
        'description_long' => 'Mithilfe hochauflösender Sensoren scannt die Sensorphalanx zunächst das Lichtspektrum, die Gaszusammensetzung und die Strahlungsemissionen einer fernen Welt und überträgt die Daten an einen Supercomputer zur Verarbeitung. Sobald die Informationen vorliegen, vergleicht der Supercomputer Änderungen im Spektrum, der Gaszusammensetzung und den Strahlungsemissionen mit einer Basislinie bekannter Veränderungen, die durch verschiedene Schiffsbewegungen verursacht werden. Die resultierenden Daten zeigen dann Aktivitäten aller Flotten innerhalb der Reichweite der Phalanx an. Um ein Überhitzen des Supercomputers zu verhindern, wird er durch den Einsatz von 5k verarbeitetem Deuterium gekühlt.
Um die Phalanx zu nutzen, klicke auf einen beliebigen Planeten in der Galaxieansicht innerhalb deiner Sensorreichweite.',
    ],

    'jump_gate' => [
        'title'            => 'Sprungtor',
        'description'      => 'Sprungtore sind riesige Transmitter, die selbst die größte Flotte in kürzester Zeit zu einem entfernten Sprungtor senden können.',
        'description_long' => 'Ein Sprungtor ist ein System riesiger Transmitter, die selbst die größten Flotten ohne Zeitverlust zu einem empfangenden Tor überall im Universum senden können. Durch die Nutzung einer dem Wurmloch ähnlichen Technologie wird kein Deuterium benötigt. Zwischen den Sprüngen muss eine Aufladezeit von wenigen Minuten vergehen, um die Regeneration zu ermöglichen. Der Transport von Rohstoffen durch das Tor ist ebenfalls nicht möglich. Mit jeder Ausbaustufe kann die Abklingzeit des Sprungtors verringert werden.',
    ],

    // -------------------------------------------------------------------------
    // Research objects (from ResearchObjects.php)
    // -------------------------------------------------------------------------

    'energy_technology' => [
        'title'            => 'Energietechnik',
        'description'      => 'Die Beherrschung verschiedener Energieformen ist Voraussetzung für viele neue Technologien.',
        'description_long' => 'Mit dem Fortschritt verschiedener Forschungsfelder wurde festgestellt, dass die bisherige Technologie der Energieverteilung nicht ausreichte, um bestimmte spezialisierte Forschungen zu beginnen. Mit jedem Ausbau der Energietechnik können neue Forschungen durchgeführt werden, die die Entwicklung fortschrittlicherer Schiffe und Verteidigungsanlagen ermöglichen.',
    ],

    'laser_technology' => [
        'title'            => 'Lasertechnik',
        'description'      => 'Die Bündelung von Licht erzeugt einen Strahl, der beim Auftreffen auf ein Objekt Schaden verursacht.',
        'description_long' => 'Laser (Lichtverstärkung durch stimulierte Emission von Strahlung) erzeugen eine intensive, energiereiche Emission kohärenten Lichts. Diese Geräte können in allen möglichen Bereichen eingesetzt werden, von optischen Computern bis hin zu schweren Laserwaffen, die mühelos durch Panzerungstechnologie schneiden. Die Lasertechnik bildet eine wichtige Grundlage für die Erforschung anderer Waffentechnologien.',
    ],

    'ion_technology' => [
        'title'            => 'Ionentechnik',
        'description'      => 'Die Konzentration von Ionen ermöglicht den Bau von Geschützen, die enormen Schaden anrichten können, und reduziert die Abrisskosten pro Stufe um 4%.',
        'description_long' => 'Ionen können konzentriert und zu einem tödlichen Strahl beschleunigt werden. Diese Strahlen können dann enormen Schaden anrichten. Unsere Wissenschaftler haben außerdem eine Technik entwickelt, die die Abrisskosten für Gebäude und Anlagen deutlich senkt. Pro Forschungsstufe sinken die Abrisskosten um 4%.',
    ],

    'hyperspace_technology' => [
        'title'            => 'Hyperraumtechnik',
        'description'      => 'Durch die Integration der 4. und 5. Dimension ist es nun möglich, einen neuen Antrieb zu erforschen, der wirtschaftlicher und effizienter ist.',
        'description_long' => 'Theoretisch basiert die Idee der Hyperraumreise auf der Existenz einer separaten, angrenzenden Dimension. Bei Aktivierung versetzt ein Hyperraumantrieb das Raumschiff in diese andere Dimension, in der es große Entfernungen in wesentlich kürzerer Zeit zurücklegen kann als im "normalen" Raum. Sobald es den Punkt im Hyperraum erreicht, der seinem Ziel im realen Raum entspricht, tritt es wieder aus.
Sobald ein ausreichendes Niveau der Hyperraumtechnik erforscht ist, ist der Hyperraumantrieb nicht mehr nur eine Theorie. Jede Verbesserung dieses Antriebs erhöht die Ladekapazität der Schiffe um 5% des Basiswerts.',
    ],

    'plasma_technology' => [
        'title'            => 'Plasmatechnik',
        'description'      => 'Eine Weiterentwicklung der Ionentechnik, die hochenergetisches Plasma beschleunigt, das verheerenden Schaden anrichtet und zusätzlich die Produktion von Metall, Kristall und Deuterium optimiert (1%/0,66%/0,33% pro Stufe).',
        'description_long' => 'Eine Weiterentwicklung der Ionentechnik, die nicht Ionen, sondern hochenergetisches Plasma beschleunigt, das beim Aufprall auf ein Objekt verheerenden Schaden anrichten kann. Unsere Wissenschaftler haben außerdem einen Weg gefunden, den Abbau von Metall und Kristall mit dieser Technologie spürbar zu verbessern.

Die Metallproduktion steigt um 1%, die Kristallproduktion um 0,66% und die Deuteriumproduktion um 0,33% pro Ausbaustufe der Plasmatechnik.',
    ],

    'combustion_drive' => [
        'title'            => 'Verbrennungstriebwerk',
        'description'      => 'Die Entwicklung dieses Antriebs macht einige Schiffe schneller, wobei jede Stufe die Geschwindigkeit um nur 10 % des Basiswerts erhöht.',
        'description_long' => 'Das Verbrennungstriebwerk ist die älteste aller Technologien, wird aber immer noch eingesetzt. Beim Verbrennungstriebwerk wird der Abgasstrahl aus Treibstoffen gebildet, die vor der Verwendung im Schiff mitgeführt werden. In einer geschlossenen Kammer sind die Drücke in jede Richtung gleich und es findet keine Beschleunigung statt. Wird am Boden der Kammer eine Öffnung vorgesehen, wird der Druck auf dieser Seite nicht mehr kompensiert. Der verbleibende Druck ergibt einen resultierenden Schub auf der der Öffnung gegenüberliegenden Seite, der das Schiff vorwärts treibt, indem die Abgase mit extrem hoher Geschwindigkeit nach hinten ausgestoßen werden.

Mit jeder Stufe des Verbrennungstriebwerks wird die Geschwindigkeit von Kleinen und Großen Transportern, Leichten Jägern, Recyclern und Spionagesonden um 10% erhöht.',
    ],

    'impulse_drive' => [
        'title'            => 'Impulstriebwerk',
        'description'      => 'Das Impulstriebwerk basiert auf dem Rückstoßprinzip. Die Weiterentwicklung dieses Antriebs macht einige Schiffe schneller, wobei jede Stufe die Geschwindigkeit um nur 20 % des Basiswerts erhöht.',
        'description_long' => 'Das Impulstriebwerk basiert auf dem Rückstoßprinzip, bei dem die stimulierte Emission von Strahlung hauptsächlich als Nebenprodukt der Kernfusion zur Energiegewinnung erzeugt wird. Zusätzlich können weitere Massen eingespritzt werden. Mit jeder Stufe des Impulstriebwerks wird die Geschwindigkeit von Bombern, Kreuzern, Schweren Jägern und Kolonieschiffen um 20% des Basiswerts erhöht. Zusätzlich werden die Kleinen Transporter mit Impulstriebwerken ausgestattet, sobald die Forschungsstufe 5 erreicht. Sobald die Impulstriebwerk-Forschung Stufe 17 erreicht hat, werden Recycler mit Impulstriebwerken nachgerüstet.

Interplanetarraketen fliegen mit jeder Stufe ebenfalls weiter.',
    ],

    'hyperspace_drive' => [
        'title'            => 'Hyperraumantrieb',
        'description'      => 'Der Hyperraumantrieb krümmt den Raum um ein Schiff. Die Entwicklung dieses Antriebs macht einige Schiffe schneller, wobei jede Stufe die Geschwindigkeit um nur 30 % des Basiswerts erhöht.',
        'description_long' => 'In unmittelbarer Nähe des Schiffes wird der Raum so gekrümmt, dass große Entfernungen sehr schnell zurückgelegt werden können. Je weiter der Hyperraumantrieb entwickelt wird, desto stärker die Raumkrümmung, wodurch die Geschwindigkeit der damit ausgestatteten Schiffe (Schlachtkreuzer, Schlachtschiffe, Zerstörer, Todessterne, Pathfinder und Reaper) um 30% pro Stufe steigt. Zusätzlich wird der Bomber mit einem Hyperraumantrieb ausgestattet, sobald die Forschung Stufe 8 erreicht. Sobald die Hyperraumantrieb-Forschung Stufe 15 erreicht, wird der Recycler mit einem Hyperraumantrieb nachgerüstet.',
    ],

    'espionage_technology' => [
        'title'            => 'Spionagetechnik',
        'description'      => 'Mit dieser Technologie können Informationen über andere Planeten und Monde gewonnen werden.',
        'description_long' => 'Die Spionagetechnik ist in erster Linie eine Weiterentwicklung der Sensortechnologie. Je fortschrittlicher diese Technologie ist, desto mehr Informationen erhält der Nutzer über Aktivitäten in seiner Umgebung.
Die Differenz zwischen der eigenen Spionagestufe und der gegnerischen ist entscheidend für Sonden. Je fortschrittlicher die eigene Spionagetechnik ist, desto mehr Informationen kann der Bericht sammeln und desto geringer ist die Chance, dass die Spionageaktivitäten entdeckt werden. Je mehr Sonden bei einer Mission gesendet werden, desto mehr Details können sie vom Zielplaneten sammeln. Gleichzeitig steigt aber auch die Entdeckungsgefahr.
Die Spionagetechnik verbessert auch die Chance, feindliche Flotten zu orten. Die Spionagestufe ist dabei entscheidend. Ab Stufe 2 wird zusätzlich zur normalen Angriffsbenachrichtigung die genaue Gesamtzahl der angreifenden Schiffe angezeigt. Ab Stufe 4 werden auch die Schiffstypen und ihre Gesamtzahl angezeigt, und ab Stufe 8 die genaue Anzahl der verschiedenen Schiffstypen.
Diese Technologie ist für einen bevorstehenden Angriff unverzichtbar, da sie Auskunft darüber gibt, ob die Zielflotte über Verteidigung verfügt. Darum sollte diese Technologie sehr früh erforscht werden.',
    ],

    'computer_technology' => [
        'title'            => 'Computertechnik',
        'description'      => 'Durch die Erhöhung der Computerkapazitäten können mehr Flotten befehligt werden. Jede Stufe der Computertechnik erhöht die maximale Flottenanzahl um eins.',
        'description_long' => 'Einmal auf eine Mission gestartet, werden Flotten hauptsächlich von einer Reihe von Computern auf dem Ursprungsplaneten gesteuert. Diese massiven Computer berechnen die genaue Ankunftszeit, steuern Kurskorrekturen nach Bedarf, berechnen Flugbahnen und regulieren Fluggeschwindigkeiten.
Mit jeder erforschten Stufe wird der Flugcomputer aufgerüstet, um einen zusätzlichen Flottenslot zu ermöglichen. Die Computertechnik sollte während des gesamten Aufbaus des Imperiums kontinuierlich weiterentwickelt werden.',
    ],

    'astrophysics' => [
        'title'            => 'Astrophysik',
        'description'      => 'Mit einem Astrophysik-Forschungsmodul können Schiffe lange Expeditionen unternehmen. Jede zweite Stufe dieser Technologie ermöglicht die Kolonisierung eines weiteren Planeten.',
        'description_long' => 'Weitere Erkenntnisse auf dem Gebiet der Astrophysik ermöglichen den Bau von Laboren, die auf immer mehr Schiffen installiert werden können. Dies macht lange Expeditionen weit in unerforschte Gebiete des Weltraums möglich. Darüber hinaus können diese Fortschritte genutzt werden, um das Universum weiter zu besiedeln. Für je zwei Stufen dieser Technologie kann ein zusätzlicher Planet nutzbar gemacht werden.',
    ],

    'intergalactic_research_network' => [
        'title'            => 'Intergalaktisches Forschungsnetzwerk',
        'description'      => 'Forscher auf verschiedenen Planeten kommunizieren über dieses Netzwerk.',
        'description_long' => 'Dies ist das Tiefraumnetzwerk zur Übermittlung von Forschungsergebnissen an die Kolonien. Mit dem IGF können schnellere Forschungszeiten erreicht werden, indem die höchststufigen Forschungslabore entsprechend der IGF-Stufe vernetzt werden.
Um zu funktionieren, muss jede Kolonie in der Lage sein, die Forschung eigenständig durchzuführen.',
    ],

    'graviton_technology' => [
        'title'            => 'Gravitonforschung',
        'description'      => 'Durch das Abfeuern einer konzentrierten Ladung von Gravitonteilchen kann ein künstliches Gravitationsfeld erzeugt werden, das Schiffe oder sogar Monde zerstören kann.',
        'description_long' => 'Ein Graviton ist ein Elementarteilchen, das masselos ist und keine Ladung besitzt. Es bestimmt die Gravitationskraft. Durch das Abfeuern einer konzentrierten Ladung von Gravitonen kann ein künstliches Gravitationsfeld erzeugt werden. Ähnlich einem Schwarzen Loch zieht es Masse in sich hinein. So können Schiffe und sogar ganze Monde zerstört werden. Um eine ausreichende Menge an Gravitonen zu erzeugen, werden enorme Energiemengen benötigt. Die Gravitonforschung ist Voraussetzung für den Bau eines zerstörerischen Todessterns.',
    ],

    'weapon_technology' => [
        'title'            => 'Waffentechnik',
        'description'      => 'Die Waffentechnik macht Waffensysteme effizienter. Jede Stufe der Waffentechnik erhöht die Waffenstärke der Einheiten um 10 % des Basiswerts.',
        'description_long' => 'Die Waffentechnik ist eine Schlüsseltechnologie und entscheidend für das Überleben gegen feindliche Imperien. Mit jeder erforschten Stufe der Waffentechnik werden die Waffensysteme auf Schiffen und Verteidigungsmechanismen zunehmend effizienter. Jede Stufe erhöht die Basisstärke der Waffen um 10% des Basiswerts.',
    ],

    'shielding_technology' => [
        'title'            => 'Schildtechnik',
        'description'      => 'Die Schildtechnik macht die Schilde auf Schiffen und Verteidigungsanlagen effizienter. Jede Stufe der Schildtechnik erhöht die Schildstärke um 10 % des Basiswerts.',
        'description_long' => 'Mit der Erfindung des Magnetosphärengenerators lernten Wissenschaftler, dass ein künstlicher Schild erzeugt werden kann, der die Besatzung in Raumschiffen nicht nur vor der rauen Weltraumstrahlung im tiefen Raum schützt, sondern auch Schutz vor feindlichem Feuer bei einem Angriff bietet. Als die Wissenschaftler die Technologie perfektioniert hatten, wurde ein Magnetosphärengenerator in alle Schiffe und Verteidigungssysteme eingebaut.

Mit jedem Stufenausbau wird der Magnetosphärengenerator aufgerüstet, was zusätzliche 10% Stärke zum Basiswert der Schilde bietet.',
    ],

    'armor_technology' => [
        'title'            => 'Raumschiffpanzerung',
        'description'      => 'Spezielle Legierungen verbessern die Panzerung von Schiffen und Verteidigungsanlagen. Die Effektivität der Panzerung kann um 10 % pro Stufe erhöht werden.',
        'description_long' => 'Die Umgebung des tiefen Weltraums ist rau. Piloten und Besatzungen auf verschiedenen Missionen waren nicht nur intensiver Weltraumstrahlung ausgesetzt, sondern auch der Gefahr, von Weltraumtrümmern getroffen oder durch feindliches Feuer zerstört zu werden. Mit der Entdeckung einer Aluminium-Lithium-Titankarbid-Legierung, die sich als leicht und haltbar erwies, wurde der Besatzung ein gewisser Schutz geboten. Mit jeder Stufe der Raumschiffpanzerung wird eine höherwertige Legierung produziert, die die Panzerungsstärke um 10% erhöht.',
    ],

    // ---- Civil Ships ----

    'small_cargo' => [
        'title'            => 'Kleiner Transporter',
        'description'      => 'Der Kleine Transporter ist ein wendiges Schiff, das Rohstoffe schnell zu anderen Planeten transportieren kann.',
        'description_long' => 'Transporter sind etwa so groß wie Jäger, verzichten jedoch auf Hochleistungsantriebe und Bordwaffen zugunsten ihrer Frachtkapazität. Daher sollte ein Transporter nur in Begleitung kampfbereiter Schiffe in Schlachten geschickt werden.

Sobald das Impulstriebwerk Forschungsstufe 5 erreicht, reist der Kleine Transporter mit erhöhter Basisgeschwindigkeit und wird mit einem Impulstriebwerk ausgestattet.',
    ],

    'large_cargo' => [
        'title'            => 'Großer Transporter',
        'description'      => 'Dieses Frachtschiff hat eine wesentlich größere Ladekapazität als der Kleine Transporter und ist dank eines verbesserten Antriebs in der Regel schneller.',
        'description_long' => 'Im Laufe der Zeit führten die Raubzüge auf Kolonien zu immer größeren erbeuteten Rohstoffmengen. Infolgedessen wurden Kleine Transporter in Massenproduktion eingesetzt, um die größeren Beutezüge zu bewältigen. Es wurde schnell klar, dass eine neue Schiffsklasse benötigt wurde, die sowohl die erbeuteten Rohstoffe maximiert als auch kosteneffizient ist. Nach langer Entwicklung wurde der Große Transporter geboren.

Um die Rohstoffe, die in den Laderäumen gelagert werden können, zu maximieren, verfügt dieses Schiff über wenig Waffen oder Panzerung. Dank des hochentwickelten eingebauten Verbrennungsmotors dient es als wirtschaftlichster Rohstofflieferant zwischen Planeten und ist am effektivsten bei Raubzügen auf feindliche Welten.',
    ],

    'colony_ship' => [
        'title'            => 'Kolonieschiff',
        'description'      => 'Mit diesem Schiff können unbewohnte Planeten besiedelt werden.',
        'description_long' => 'Im 20. Jahrhundert beschloss die Menschheit, nach den Sternen zu greifen. Zuerst war es die Landung auf dem Mond. Danach wurde eine Raumstation gebaut. Der Mars wurde kurz darauf besiedelt. Es wurde bald festgestellt, dass unser Wachstum von der Besiedlung anderer Welten abhängt. Wissenschaftler und Ingenieure aus aller Welt versammelten sich, um die größte Errungenschaft der Menschheit zu entwickeln. Das Kolonieschiff wurde geboren.

Dieses Schiff wird eingesetzt, um einen neu entdeckten Planeten für die Besiedlung vorzubereiten. Nach der Ankunft wird das Schiff sofort in Wohnraum umgewandelt, um die Besiedelung und den Abbau der neuen Welt zu unterstützen. Die maximale Planetenanzahl wird dabei durch den Fortschritt in der Astrophysik-Forschung bestimmt. Zwei neue Stufen der Astrophysik ermöglichen die Kolonisierung eines zusätzlichen Planeten.',
    ],

    'recycler' => [
        'title'            => 'Recycler',
        'description'      => 'Recycler sind die einzigen Schiffe, die Trümmerfelder im Orbit eines Planeten nach einem Kampf einsammeln können.',
        'description_long' => 'Kämpfe im Weltraum nahmen immer größere Ausmaße an. Tausende von Schiffen wurden zerstört und die Rohstoffe ihrer Überreste schienen für immer in den Trümmerfeldern verloren. Normale Frachtschiffe konnten sich diesen Feldern nicht nähern, ohne erhebliche Schäden zu riskieren.
Eine kürzliche Entwicklung in der Schildtechnologie umging dieses Problem effizient. Eine neue Schiffsklasse wurde geschaffen, die den Transportern ähnelt: die Recycler. Ihre Bemühungen halfen, die verloren geglaubten Rohstoffe zu bergen und wiederzuverwerten. Die Trümmer stellten dank der neuen Schilde keine echte Gefahr mehr dar.

Sobald die Impulstriebwerk-Forschung Stufe 17 erreicht hat, werden Recycler mit Impulstriebwerken nachgerüstet. Sobald die Hyperraumantrieb-Forschung Stufe 15 erreicht, werden Recycler mit Hyperraumantrieben nachgerüstet.',
    ],

    'espionage_probe' => [
        'title'            => 'Spionagesonde',
        'description'      => 'Spionagesonden sind kleine, wendige Drohnen, die über große Entfernungen Daten über Flotten und Planeten liefern.',
        'description_long' => 'Spionagesonden sind kleine, wendige Drohnen, die Daten über Flotten und Planeten liefern. Ausgestattet mit speziell entwickelten Triebwerken können sie große Entfernungen in nur wenigen Minuten zurücklegen. Sobald sie sich im Orbit um den Zielplaneten befinden, sammeln sie schnell Daten und übermitteln den Bericht über das Tiefraumnetzwerk zur Auswertung. Dabei besteht jedoch ein Risiko: Während der Übertragung des Berichts an das Netzwerk kann das Signal vom Ziel erkannt und die Sonden zerstört werden.',
    ],

    'solar_satellite' => [
        'title'            => 'Solarsatellit',
        'description'      => 'Solarsatelliten sind einfache Plattformen aus Solarzellen in einer hohen, stationären Umlaufbahn. Sie sammeln Sonnenlicht und übertragen es per Laser an die Bodenstation.',
        'description_long' => 'Wissenschaftler entdeckten eine Methode, elektrische Energie mithilfe speziell entwickelter Satelliten in einer geosynchronen Umlaufbahn an die Kolonie zu übertragen. Solarsatelliten sammeln Sonnenenergie und übertragen sie mittels fortschrittlicher Lasertechnologie an eine Bodenstation. Die Effizienz eines Solarsatelliten hängt von der Stärke der empfangenen Sonnenstrahlung ab. Grundsätzlich ist die Energieproduktion in sonnennaheren Umlaufbahnen größer als bei sonnenfernen Planeten.
Aufgrund ihres guten Kosten-Leistungs-Verhältnisses können Solarsatelliten viele Energieprobleme lösen. Aber Vorsicht: Solarsatelliten können in Kämpfen leicht zerstört werden.',
    ],

    'crawler' => [
        'title'            => 'Crawler',
        'description'      => 'Crawler erhöhen die Produktion von Metall, Kristall und Deuterium auf ihrem zugewiesenen Planeten um jeweils 0,02%, 0,02% und 0,02%. Als Kollektor steigt die Produktion ebenfalls. Der maximale Gesamtbonus hängt von der Gesamtstufe der Minen ab.',
        'description_long' => 'Der Crawler ist ein großes Grabenfahrzeug, das die Produktion von Minen und Synthetisierern erhöht. Er ist wendiger als er aussieht, aber nicht besonders robust. Jeder Crawler erhöht die Metallproduktion um 0,02%, die Kristallproduktion um 0,02% und die Deuteriumproduktion um 0,02%. Als Kollektor steigt die Produktion ebenfalls. Der maximale Gesamtbonus hängt von der Gesamtstufe der Minen ab.',
    ],

    'pathfinder' => [
        'title'            => 'Pathfinder',
        'description'      => 'Der Pathfinder ist ein schnelles und wendiges Schiff, das speziell für Expeditionen in unbekannte Sektoren des Weltraums gebaut wurde.',
        'description_long' => 'Der Pathfinder ist die neueste Entwicklung in der Erkundungstechnologie. Dieses Schiff wurde speziell für Mitglieder der Entdecker-Klasse entwickelt, um deren Potenzial zu maximieren. Ausgestattet mit fortschrittlichen Scansystemen und einem großen Laderaum zur Bergung von Rohstoffen, zeichnet sich der Pathfinder bei Expeditionen aus. Seine hochentwickelten Sensoren können wertvolle Rohstoffe und Anomalien erkennen, die anderen Schiffen entgehen würden. Das Schiff kombiniert hohe Geschwindigkeit mit guter Frachtkapazität und eignet sich perfekt für schnelle Erkundungsmissionen und die Rohstoffgewinnung aus entfernten Sektoren.',
    ],

    // ---- Military Ships ----

    'light_fighter' => [
        'title'            => 'Leichter Jäger',
        'description'      => 'Dies ist das erste Kampfschiff, das jeder Imperator bauen wird. Der Leichte Jäger ist ein wendiges Schiff, aber allein verwundbar. In großer Zahl können sie eine ernsthafte Bedrohung für jedes Imperium darstellen. Sie sind die ersten, die Kleine und Große Transporter zu feindlichen Planeten mit geringer Verteidigung begleiten.',
        'description_long' => 'Dies ist das erste Kampfschiff, das jeder Imperator bauen wird. Der Leichte Jäger ist ein wendiges Schiff, aber allein verwundbar. In großer Zahl können sie eine ernsthafte Bedrohung für jedes Imperium darstellen. Sie sind die ersten, die Kleine und Große Transporter zu feindlichen Planeten mit geringer Verteidigung begleiten.',
    ],

    'heavy_fighter' => [
        'title'            => 'Schwerer Jäger',
        'description'      => 'Dieser Jäger ist besser gepanzert und hat eine höhere Angriffsstärke als der Leichte Jäger.',
        'description_long' => 'Bei der Entwicklung des Schweren Jägers erreichten die Forscher einen Punkt, an dem herkömmliche Antriebe keine ausreichende Leistung mehr boten. Um das Schiff optimal zu bewegen, wurde erstmals das Impulstriebwerk eingesetzt. Dies erhöhte die Kosten, eröffnete aber auch neue Möglichkeiten. Durch die Nutzung dieses Antriebs blieb mehr Energie für Waffen und Schilde übrig; zudem wurden hochwertige Materialien für diese neue Jägerfamilie verwendet. Mit diesen Änderungen markiert der Schwere Jäger eine neue Ära in der Schiffstechnologie und bildet die Grundlage für die Kreuzertechnologie.

Etwas größer als der Leichte Jäger verfügt der Schwere Jäger über dickere Hüllen, die mehr Schutz bieten, und stärkere Bewaffnung.',
    ],

    'cruiser' => [
        'title'            => 'Kreuzer',
        'description'      => 'Kreuzer sind fast dreimal so stark gepanzert wie Schwere Jäger und haben mehr als doppelt so viel Feuerkraft. Zudem sind sie sehr schnell.',
        'description_long' => 'Mit der Entwicklung des schweren Lasers und der Ionenkanone erlitten Leichte und Schwere Jäger eine alarmierend hohe Anzahl von Niederlagen, die mit jedem Raubzug zunahmen. Trotz vieler Modifikationen an Waffenstärke und Panzerung konnten diese nicht schnell genug gesteigert werden, um diesen neuen Verteidigungsmaßnahmen wirksam entgegenzutreten. Daher wurde beschlossen, eine neue Schiffsklasse zu bauen, die mehr Panzerung und mehr Feuerkraft vereint. Als Ergebnis jahrelanger Forschung und Entwicklung wurde der Kreuzer geboren.

Kreuzer sind fast dreimal so stark gepanzert wie Schwere Jäger und besitzen mehr als doppelt so viel Feuerkraft wie jedes existierende Kampfschiff. Sie erreichen zudem Geschwindigkeiten, die jedes jemals gebaute Raumfahrzeug weit übertreffen. Fast ein Jahrhundert lang dominierten Kreuzer das Universum. Mit der Entwicklung von Gaußkanonen und Plasmawerfern endete jedoch ihre Vorherrschaft. Sie werden heute noch gegen Jägergruppen eingesetzt, aber nicht mehr so dominant wie zuvor.',
    ],

    'battle_ship' => [
        'title'            => 'Schlachtschiff',
        'description'      => 'Schlachtschiffe bilden das Rückgrat einer Flotte. Ihre schweren Kanonen, die hohe Geschwindigkeit und die großen Frachträume machen sie zu ernstzunehmenden Gegnern.',
        'description_long' => 'Als sich herausstellte, dass der Kreuzer gegenüber der zunehmenden Anzahl von Verteidigungsanlagen an Boden verlor und die Schiffsverluste bei Missionen ein inakzeptables Niveau erreichten, wurde beschlossen, ein Schiff zu bauen, das denselben Verteidigungsanlagen mit möglichst geringen Verlusten standhalten konnte. Nach umfangreicher Entwicklung wurde das Schlachtschiff geboren. Gebaut um den größten Schlachten standzuhalten, verfügt das Schlachtschiff über große Frachträume, schwere Kanonen und hohe Hyperantriebsgeschwindigkeit. Nach seiner Entwicklung erwies es sich als das Rückgrat der Flotte jedes raubenden Imperators.',
    ],

    'battlecruiser' => [
        'title'            => 'Schlachtkreuzer',
        'description'      => 'Der Schlachtkreuzer ist hochspezialisiert auf das Abfangen feindlicher Flotten.',
        'description_long' => 'Dieses Schiff ist eines der fortschrittlichsten Kampfschiffe, die je entwickelt wurden, und ist besonders tödlich bei der Vernichtung angreifender Flotten. Mit seinen verbesserten Laserkanonen an Bord und dem fortschrittlichen Hyperraumantrieb ist der Schlachtkreuzer eine ernstzunehmende Kraft bei jedem Angriff. Aufgrund des Schiffsdesigns und des großen Waffensystems mussten die Frachträume reduziert werden, dies wird jedoch durch den geringeren Treibstoffverbrauch kompensiert.',
    ],

    'bomber' => [
        'title'            => 'Bomber',
        'description'      => 'Der Bomber wurde speziell entwickelt, um die planetaren Verteidigungsanlagen einer Welt zu zerstören.',
        'description_long' => 'Im Laufe der Jahrhunderte, als die Verteidigungsanlagen immer größer und ausgefeilter wurden, wurden Flotten mit alarmierender Rate zerstört. Es wurde beschlossen, dass ein neues Schiff benötigt wird, um Verteidigungen zu durchbrechen und maximale Ergebnisse zu erzielen. Nach Jahren der Forschung und Entwicklung wurde der Bomber erschaffen.

Mit lasergelenkter Zielausrüstung und Plasmabomben sucht und zerstört der Bomber jeden Verteidigungsmechanismus, den er finden kann. Sobald der Hyperraumantrieb auf Stufe 8 entwickelt ist, wird der Bomber mit dem Hyperraumantrieb nachgerüstet und kann mit höheren Geschwindigkeiten fliegen.',
    ],

    'destroyer' => [
        'title'            => 'Zerstörer',
        'description'      => 'Der Zerstörer ist der König der Kriegsschiffe.',
        'description_long' => 'Der Zerstörer ist das Ergebnis jahrelanger Arbeit und Entwicklung. Mit der Entwicklung von Todessternen wurde entschieden, dass eine Schiffsklasse benötigt wird, die sich gegen eine solch massive Waffe verteidigen kann. Dank seiner verbesserten Zielsensoren, Mehrphalanx-Ionenkanonen, Gaußkanonen und Plasmawerfer erwies sich der Zerstörer als eines der furchteinflößendsten je gebauten Schiffe.

Da der Zerstörer sehr groß ist, ist seine Manövrierfähigkeit stark eingeschränkt, was ihn eher zu einer Kampfstation als zu einem Kampfschiff macht. Die mangelnde Manövrierfähigkeit wird durch seine schiere Feuerkraft wettgemacht, allerdings kostet er auch erhebliche Mengen an Deuterium für Bau und Betrieb.',
    ],

    'deathstar' => [
        'title'            => 'Todesstern',
        'description'      => 'Die Zerstörungskraft des Todessterns ist unübertroffen.',
        'description_long' => 'Der Todesstern ist das mächtigste je gebaute Schiff. Dieses mondgroße Schiff ist das einzige Schiff, das vom Boden aus mit bloßem Auge sichtbar ist. Wenn man es bemerkt, ist es leider zu spät, um noch etwas zu unternehmen.

Bewaffnet mit einer gigantischen Gravitonkanone, dem fortschrittlichsten jemals im Universum geschaffenen Waffensystem, hat dieses massive Schiff nicht nur die Fähigkeit, ganze Flotten und Verteidigungen zu zerstören, sondern auch ganze Monde. Nur die fortschrittlichsten Imperien haben die Fähigkeit, ein Schiff dieser gewaltigen Größe zu bauen.',
    ],

    'reaper' => [
        'title'            => 'Reaper',
        'description'      => 'Der Reaper ist ein mächtiges Kampfschiff, das auf aggressive Raubzüge und das Einsammeln von Trümmerfeldern spezialisiert ist.',
        'description_long' => 'Der Reaper stellt den Höhepunkt der militärischen Ingenieurskunst der General-Klasse dar. Dieses schwer bewaffnete Schiff wurde für Kommandanten entwickelt, die sowohl Kampfstärke als auch taktische Flexibilität schätzen. Obwohl seine Hauptaufgabe der Kampf ist, verfügt der Reaper über verstärkte Frachträume, die es ihm ermöglichen, nach der Schlacht Trümmerfelder einzusammeln. Seine fortschrittlichen Zielsysteme und die schwere Panzerung machen ihn zu einem furchteinflößenden Gegner, während sein Doppelzweck-Design bedeutet, dass er sowohl vom Schlachtfeldchaos profitieren als auch es verursachen kann. Das Schiff ist mit modernster Waffentechnologie ausgestattet und kann sich gegen weitaus größere Schiffe behaupten.',
    ],

    // ---- Defense ----

    'rocket_launcher' => [
        'title'            => 'Raketenwerfer',
        'description'      => 'Der Raketenwerfer ist eine einfache, kostengünstige Verteidigungsoption.',
        'description_long' => 'Die erste grundlegende Verteidigungslinie. Dies sind einfache bodengestützte Abschussanlagen, die konventionelle Sprengkopfraketen auf angreifende feindliche Ziele abfeuern. Da sie billig zu bauen sind und keine Forschung benötigt wird, eignen sie sich gut zur Verteidigung gegen Raubzüge, verlieren aber bei größeren Angriffen an Wirksamkeit. Sobald der Bau fortschrittlicherer Verteidigungswaffensysteme beginnt, werden Raketenwerfer zu einfachem Kanonenfutter, das den zerstörerischeren Waffen ermöglicht, über einen längeren Zeitraum größeren Schaden anzurichten.

Nach einem Kampf besteht eine Chance von bis zu 70 %, dass zerstörte Verteidigungsanlagen wiederhergestellt werden können.',
    ],

    'light_laser' => [
        'title'            => 'Leichtes Lasergeschütz',
        'description'      => 'Konzentriertes Beschießen eines Ziels mit Photonen kann deutlich größeren Schaden verursachen als herkömmliche ballistische Waffen.',
        'description_long' => 'Mit der technologischen Entwicklung und der Erschaffung immer ausgereifterer Schiffe wurde festgestellt, dass eine stärkere Verteidigungslinie benötigt wurde. Mit dem Fortschritt der Lasertechnik wurde eine neue Waffe entworfen, die die nächste Verteidigungsstufe bietet. Leichte Lasergeschütze sind einfache bodengestützte Waffen, die spezielle Zielsysteme nutzen, um den Feind zu verfolgen und einen hochintensiven Laser abzufeuern, der durch die Hülle des Ziels schneidet. Um kosteneffizient zu bleiben, wurden sie mit einem verbesserten Schildsystem ausgestattet, die strukturelle Integrität ist jedoch dieselbe wie beim Raketenwerfer.

Nach einem Kampf besteht eine Chance von bis zu 70 %, dass zerstörte Verteidigungsanlagen wiederhergestellt werden können.',
    ],

    'heavy_laser' => [
        'title'            => 'Schweres Lasergeschütz',
        'description'      => 'Das Schwere Lasergeschütz ist die logische Weiterentwicklung des Leichten Lasergeschützes.',
        'description_long' => 'Das Schwere Lasergeschütz ist eine praktische, verbesserte Version des Leichten Lasergeschützes. Ausgewogener als das Leichte Lasergeschütz mit verbesserter Legierungszusammensetzung, nutzt es stärkere, dichter gebündelte Strahlen und noch bessere Bordzielsysteme.

Nach einem Kampf besteht eine Chance von bis zu 70 %, dass zerstörte Verteidigungsanlagen wiederhergestellt werden können.',
    ],

    'gauss_cannon' => [
        'title'            => 'Gaußkanone',
        'description'      => 'Die Gaußkanone feuert tonnenweise schwere Projektile mit hoher Geschwindigkeit.',
        'description_long' => 'Lange Zeit galten Projektilwaffen angesichts moderner thermonuklearer und Energietechnologie und aufgrund der Entwicklung des Hyperantriebs und verbesserter Panzerung als veraltet. Bis genau die Energietechnologie, die sie einst verdrängt hatte, ihnen half, ihre etablierte Position wiederzuerlangen.
Eine Gaußkanone ist eine große Version des Teilchenbeschleunigers. Extrem schwere Geschosse werden mit enormer elektromagnetischer Kraft beschleunigt und erreichen Mündungsgeschwindigkeiten, die den Schmutz um das Geschoss in der Atmosphäre zum Glühen bringen. Diese Waffe ist beim Abfeuern so kraftvoll, dass sie einen Überschallknall erzeugt. Moderne Panzerungen und Schilde können der Kraft kaum standhalten, oft wird das Ziel durch die Wucht des Geschosses vollständig durchschlagen. Verteidigungsanlagen deaktivieren sich, sobald sie zu stark beschädigt sind.

Nach einem Kampf besteht eine Chance von bis zu 70 %, dass zerstörte Verteidigungsanlagen wiederhergestellt werden können.',
    ],

    'ion_cannon' => [
        'title'            => 'Ionengeschütz',
        'description'      => 'Das Ionengeschütz feuert einen kontinuierlichen Strahl beschleunigter Ionen, der beim Auftreffen beträchtlichen Schaden verursacht.',
        'description_long' => 'Ein Ionengeschütz ist eine Waffe, die Strahlen von Ionen (positiv oder negativ geladene Teilchen) abfeuert. Das Ionengeschütz ist eigentlich eine Art Teilchenkanone; nur die verwendeten Teilchen sind ionisiert. Aufgrund ihrer elektrischen Ladungen haben sie auch das Potenzial, elektronische Geräte und alles andere mit einer elektrischen oder ähnlichen Energiequelle zu deaktivieren, durch ein Phänomen bekannt als elektromagnetischer Puls (EMP-Effekt). Dank des stark verbesserten Schildsystems dieser Kanone bietet sie verbesserten Schutz für die größeren, zerstörerischeren Verteidigungswaffen.

Nach einem Kampf besteht eine Chance von bis zu 70 %, dass zerstörte Verteidigungsanlagen wiederhergestellt werden können.',
    ],

    'plasma_turret' => [
        'title'            => 'Plasmawerfer',
        'description'      => 'Plasmawerfer setzen die Energie einer Sonneneruption frei und übertreffen sogar den Zerstörer in ihrer zerstörerischen Wirkung.',
        'description_long' => 'Eines der fortschrittlichsten jemals entwickelten Verteidigungswaffensysteme: Der Plasmawerfer nutzt eine große nukleare Reaktorbrennstoffzelle, um einen elektromagnetischen Beschleuniger anzutreiben, der einen Puls oder Toroid aus Plasma abfeuert. Während des Betriebs erfasst der Plasmawerfer zunächst ein Ziel und beginnt den Abfeuerprozess. Eine Plasmakugel wird im Kern des Werfers erzeugt, indem Gase überhitzt und komprimiert werden, wobei ihnen ihre Ionen entzogen werden. Sobald das Gas überhitzt, komprimiert und eine Plasmakugel erzeugt wurde, wird sie in den elektromagnetischen Beschleuniger geladen und aktiviert. Die Plasmakugel wird dann mit extrem hoher Geschwindigkeit auf das vorgesehene Ziel abgefeuert. Aus Sicht des Ziels ist die sich nähernde bläuliche Plasmakugel beeindruckend, aber sobald sie einschlägt, verursacht sie sofortige Zerstörung.

Verteidigungsanlagen deaktivieren sich, sobald sie zu stark beschädigt sind. Nach einem Kampf besteht eine Chance von bis zu 70 %, dass zerstörte Verteidigungsanlagen wiederhergestellt werden können.',
    ],

    'small_shield_dome' => [
        'title'            => 'Kleine Schildkuppel',
        'description'      => 'Die Kleine Schildkuppel umhüllt einen gesamten Planeten mit einem Feld, das eine enorme Menge an Energie absorbieren kann.',
        'description_long' => 'Die Besiedlung neuer Welten brachte eine neue Gefahr mit sich: Weltraumtrümmer. Ein großer Asteroid könnte die Welt und alle Bewohner leicht auslöschen. Fortschritte in der Schildtechnologie boten Wissenschaftlern eine Möglichkeit, einen Schild zu entwickeln, der einen ganzen Planeten nicht nur vor Weltraumtrümmern, sondern auch vor feindlichen Angriffen schützt. Durch die Erzeugung eines großen elektromagnetischen Feldes um den Planeten wurden Weltraumtrümmer, die den Planeten normalerweise zerstört hätten, abgelenkt, und Angriffe feindlicher Imperien abgewehrt. Die ersten Generatoren waren groß und der Schild bot mäßigen Schutz, aber es wurde später festgestellt, dass kleine Schilde keinen ausreichenden Schutz vor größeren Angriffen boten. Die Kleine Schildkuppel war der Vorläufer eines stärkeren, fortschrittlicheren planetaren Schildsystems.

Nach einem Kampf besteht eine Chance von bis zu 70 %, dass zerstörte Verteidigungsanlagen wiederhergestellt werden können.',
    ],

    'large_shield_dome' => [
        'title'            => 'Große Schildkuppel',
        'description'      => 'Die Weiterentwicklung der Kleinen Schildkuppel kann deutlich mehr Energie aufnehmen, um Angriffen standzuhalten.',
        'description_long' => 'Die Große Schildkuppel ist der nächste Schritt in der Weiterentwicklung planetarer Schilde und das Ergebnis jahrelanger Verbesserungsarbeit an der Kleinen Schildkuppel. Gebaut um einem stärkeren Sperrfeuer feindlichen Feuers standzuhalten, bieten Große Kuppeln durch ein stärker aufgeladenes elektromagnetisches Feld einen längeren Schutz vor dem Zusammenbruch.

Nach einem Kampf besteht eine Chance von bis zu 70 %, dass zerstörte Verteidigungsanlagen wiederhergestellt werden können.',
    ],

    'anti_ballistic_missile' => [
        'title'            => 'Abfangrakete',
        'description'      => 'Abfangraketen zerstören angreifende Interplanetarraketen.',
        'description_long' => 'Abfangraketen (ABM) sind die einzige Verteidigungslinie gegen Interplanetarraketen (IPM), die den Planeten oder Mond angreifen. Wenn ein IPM-Start erkannt wird, werden diese Raketen automatisch scharf gemacht, verarbeiten einen Startcode in ihren Flugcomputern, erfassen die ankommende IPM und starten zum Abfangen. Während des Fluges wird die Ziel-IPM ständig verfolgt und Kurskorrekturen werden angewandt, bis die ABM das Ziel erreicht und die angreifende IPM zerstört. Jede ABM zerstört eine ankommende IPM.',
    ],

    'interplanetary_missile' => [
        'title'            => 'Interplanetarrakete',
        'description'      => 'Interplanetarraketen zerstören feindliche Verteidigungsanlagen.',
        'description_long' => 'Interplanetarraketen (IPM) sind die offensive Waffe zur Zerstörung der Verteidigungsanlagen des Ziels. Mit modernster Verfolgungstechnologie zielt jede Rakete auf eine bestimmte Anzahl von Verteidigungsanlagen zur Zerstörung. Bestückt mit einer Antimateriebombe entfalten sie eine so schwere Zerstörungskraft, dass zerstörte Schilde und Verteidigungen nicht repariert werden können. Die einzige Möglichkeit, diesen Raketen entgegenzuwirken, sind Abfangraketen.',
    ],

    // ---- Shop Booster Items ----

    'kraken' => [
        'title'       => 'KRAKEN',
        'description' => 'Reduziert die Bauzeit von Gebäuden, die sich derzeit im Bau befinden, um <b>:duration</b>.',
    ],

    'detroid' => [
        'title'       => 'DETROID',
        'description' => 'Reduziert die Bauzeit aktueller Werftaufträge um <b>:duration</b>.',
    ],

    'newtron' => [
        'title'       => 'NEWTRON',
        'description' => 'Reduziert die Forschungszeit aller laufenden Forschungen um <b>:duration</b>.',
    ],
];
