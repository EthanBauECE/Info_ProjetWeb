<header>
    <div class="header-container">
        <img src="./images/medicare_logo.png" alt="Logo Medicare" class="logo">
        <h1>Medicare : Services Médicaux</h1>
    </div>
    <nav>
        <div class="nav-left">
            <ul>
                <li><a href="index.php">Accueil</a></li>
                <li class="dropdown">
                    <a href="parcourir.php">Tout Parcourir</a>
                    <ul class="dropdown-menu">
                        <li><a href="medecine_general.php">Médecine générale</a></li>
                        <li><a href="medecins_special.php">Médecins spécialisés</a></li>
                        <li><a href="laboratoire.php">Laboratoire de biologie médicale</a></li>
                    </ul>
                </li>
                <li><a href="rdv.php">Rendez-vous</a></li>
            </ul>
        </div>

        <div class="nav-center">
            <form class="search-form" action="/search" method="get">
                <input type="text" name="query" placeholder="Rechercher sur le site...">
                <button type="submit">Rechercher</button>
            </form>
        </div>

        <div class="nav-right">
            <a href="login.php" class="account-icon">
                <img src="./images/monCompte.png" alt="Mon Compte">
            </a>
        </div>
    </nav>
</header>