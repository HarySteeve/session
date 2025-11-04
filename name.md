## TODO mercredi
### Resaka probeleme de session
Variable session **stocke any amin'ny RAM**
#### Approchoe 
1. Ao anatin'ny memoire ny pb => aza *atao* ao anatin'ny memoire ny variable de session
    Anatin'ny PHP irery no fantatran'i Mr hoe mipropose antsika session handler. Methode azo surchagenay dia iny fonction iny no antsoina dia tonga ao le anaran'ilay variable tiana ho sauvegardena sy ny valeur => Rehefa azo ilay session de stockena any amin'ny base de donne

    NB: Jerena ilay probleme de session
    Page.php ray asiana session => angatahana hoe inona ilay ny couleur ny session dia miteny hoe zao ilay couleur => dia normalement raha iverenana iny navigateur dia tonga dia tokony miteny hoe zao le couleur ako
    Raha sokafana ny nav vao2 dia lasa miverina manontany indray

    Aveo atao amin'ny HA-PROXY 
        _ao amin'ny server1 no sauvegarde ilay session
        _au fur et a mesure actualisena ilay page dia lasa mivadika hoe inona indray no ... satria lasa server2 no miasa