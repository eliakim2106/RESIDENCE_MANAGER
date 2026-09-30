@extends('layouts.admin')

@section('title', 'Tableau de bord')

@section('content')
<div class="page-header">

    <div>

        <h1>Tableau de bord</h1>

        <div class="title-line"></div>

    </div>

    <div class="header-actions">

        <button class="date-btn">

            <i class="fa-regular fa-calendar"></i>

            {{ now()->format('d/m/Y') }}

        </button>

        <button class="export-btn">

            <i class="fa-solid fa-download"></i>

            Exporter le rapport

        </button>

    </div>

</div>

<!-- KPI -->

<div class="stats-grid">

    <!-- toutes tes cartes -->
    <div class="stat-card">

        <div class="stat-header">

            <div class="stat-icon blue">

                <i class="fa-solid fa-building"></i>

            </div>

            <div class="stat-badge success">

                +12.5%

            </div>

        </div>

        <div class="stat-content">

            <span class="stat-label">

                Établissements

            </span>

            <h2>

                24

            </h2>

            <p>

                vs mois dernier

            </p>

        </div>

    </div>

    <div class="stat-card">

        <div class="stat-header">

            <div class="stat-icon gold">

                <i class="fa-solid fa-bed"></i>

            </div>

            <div class="stat-badge success">

                +8.2%

            </div>

        </div>

        <div class="stat-content">

            <span class="stat-label">

                Unités

            </span>

            <h2>

                142

            </h2>

            <p>

                vs mois dernier

            </p>

        </div>

    </div>

    <div class="stat-card">

        <div class="stat-header">

            <div class="stat-icon blue">

                <i class="fa-solid fa-calendar-check"></i>

            </div>

            <div class="stat-badge success">

                +15.3%

            </div>

        </div>

        <div class="stat-content">

            <span class="stat-label">

                Réservations

            </span>

            <h2>

                58

            </h2>

            <p>

                vs mois dernier

            </p>

        </div>

    </div>

    <div class="stat-card">

        <div class="stat-header">

            <div class="stat-icon gold">

                <i class="fa-solid fa-wallet"></i>

            </div>

            <div class="stat-badge success">

                +18.7%

            </div>

        </div>

        <div class="stat-content">

            <span class="stat-label">

                Revenus

            </span>

            <h2>

                12.7M

            </h2>

            <p>

                FCFA ce mois

            </p>

        </div>

    </div>

    <div class="stat-card">

        <div class="stat-header">

            <div class="stat-icon blue">

                <i class="fa-solid fa-users"></i>

            </div>

            <div class="stat-badge success">

                +9.4%

            </div>

        </div>

        <div class="stat-content">

            <span class="stat-label">

                Clients

            </span>

            <h2>

                312

            </h2>

            <p>

                vs mois dernier

            </p>

        </div>

    </div>

</div>

<div class="analytics-card">

    <!-- tout ton contenu -->
    <div class="analytics-header">

        <h2>Analyse des réservations</h2>

        <div class="analytics-actions">

            <div class="custom-select">

                <i class="fa-regular fa-calendar"></i>

                <select>

                    <option>6 derniers mois</option>
                    <option>12 derniers mois</option>

                </select>

            </div>

        </div>

    </div>

    <div class="analytics-body">

        <!-- Graphique -->

        <div class="analytics-chart">

            <h4>Évolution des réservations</h4>

            <div class="chart-placeholder">

                Graphique Chart.js

            </div>

        </div>

        <!-- Donut -->

        <div class="analytics-donut">

            <h4>Répartition</h4>

            <div class="donut-placeholder">

                Donut Chart

            </div>

        </div>

        <!-- Activités -->

        <div class="analytics-activities">

            <h4>Activités récentes</h4>

            <div class="activity-list">

                <!-- activité -->

            </div>

        </div>

    </div>

</div>

<!-- Graphiques -->

<div class="charts-grid">

</div>

<!-- Tableau -->

<div class="bottom-grid">

</div>
@endsection
