<?php
require_once 'includes/session.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Size Guide - ShoeARizz</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        * {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #f8f9fa;
            color: #111111;
            line-height: 1.6;
        }

        .size-guide-container {
            padding-top: 120px;
            padding-bottom: 60px;
            min-height: 100vh;
        }

        .size-guide-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .size-guide-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #111111;
            margin-bottom: 1rem;
        }

        .size-guide-header p {
            font-size: 1.1rem;
            color: #666666;
            max-width: 600px;
            margin: 0 auto;
        }

        .guide-section {
            background: #ffffff;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .guide-section h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #111111;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .guide-section h2 i {
            color: #3498db;
        }

        .size-chart-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .size-chart-table th {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #ffffff;
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .size-chart-table td {
            padding: 0.8rem 1rem;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
            font-weight: 500;
        }

        .size-chart-table tbody tr:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        .size-chart-table tbody tr:last-child td {
            border-bottom: none;
        }

        .measurement-tips {
            background: linear-gradient(135deg, #e8f4fd, #f0f8ff);
            border-left: 4px solid #3498db;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 1.5rem 0;
        }

        .measurement-tips h3 {
            color: #2980b9;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .measurement-tips ul {
            list-style: none;
            padding: 0;
        }

        .measurement-tips li {
            margin-bottom: 0.8rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .measurement-tips li i {
            color: #3498db;
            margin-top: 0.2rem;
            flex-shrink: 0;
        }

        .fit-guide {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .fit-card {
            background: #ffffff;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .fit-card:hover {
            border-color: #3498db;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(52, 152, 219, 0.1);
        }

        .fit-card i {
            font-size: 2rem;
            color: #3498db;
            margin-bottom: 1rem;
        }

        .fit-card h4 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #111111;
            margin-bottom: 0.5rem;
        }

        .fit-card p {
            color: #666666;
            font-size: 0.9rem;
        }

        .back-button {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #ffffff;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            margin-top: 2rem;
        }

        .back-button:hover {
            background: linear-gradient(135deg, #2980b9, #1f5f8b);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        @media (max-width: 768px) {
            .size-guide-header h1 {
                font-size: 2rem;
            }

            .guide-section {
                padding: 1.5rem;
            }

            .size-chart-table th,
            .size-chart-table td {
                padding: 0.6rem 0.5rem;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="size-guide-container">
        <div class="container">
            <!-- Header -->
            <div class="size-guide-header">
                <h1><i class="fas fa-ruler-combined"></i> Size Guide</h1>
                <p>Find your perfect fit with our comprehensive size guide. Accurate sizing ensures maximum comfort and performance.</p>
            </div>

            <!-- Size Chart Section -->
            <div class="guide-section">
                <h2><i class="fas fa-chart-bar"></i> Size Conversion Chart</h2>
                <div class="table-responsive">
                    <table class="size-chart-table">
                        <thead>
                            <tr>
                                <th>US Men's</th>
                                <th>US Women's</th>
                                <th>Philippines (CM)</th>
                                <th>EU Size</th>
                                <th>UK Size</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>7</td><td>8.5</td><td>25.0</td><td>40</td><td>6</td></tr>
                            <tr><td>7.5</td><td>9</td><td>25.4</td><td>40.5</td><td>6.5</td></tr>
                            <tr><td>8</td><td>9.5</td><td>25.7</td><td>41</td><td>7</td></tr>
                            <tr><td>8.5</td><td>10</td><td>26.0</td><td>42</td><td>7.5</td></tr>
                            <tr><td>9</td><td>10.5</td><td>26.7</td><td>42.5</td><td>8</td></tr>
                            <tr><td>9.5</td><td>11</td><td>27.0</td><td>43</td><td>8.5</td></tr>
                            <tr><td>10</td><td>11.5</td><td>27.3</td><td>44</td><td>9</td></tr>
                            <tr><td>10.5</td><td>12</td><td>27.9</td><td>44.5</td><td>9.5</td></tr>
                            <tr><td>11</td><td>12.5</td><td>28.3</td><td>45</td><td>10</td></tr>
                            <tr><td>11.5</td><td>13</td><td>28.6</td><td>45.5</td><td>10.5</td></tr>
                            <tr><td>12</td><td>13.5</td><td>29.0</td><td>46</td><td>11</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- How to Measure Section -->
            <div class="guide-section">
                <h2><i class="fas fa-ruler"></i> How to Measure Your Feet</h2>
                <div class="measurement-tips">
                    <h3><i class="fas fa-lightbulb"></i> Step-by-Step Instructions</h3>
                    <ul>
                        <li><i class="fas fa-check-circle"></i> <strong>Best time to measure:</strong> Measure your feet in the evening when they are at their largest</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Wear socks:</strong> Measure while wearing the type of socks you'll wear with the shoes</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Stand up:</strong> Measure while standing with your full weight on your feet</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Measure both feet:</strong> Use the measurement of your larger foot</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Use a ruler:</strong> Place a ruler or measuring tape from your heel to your longest toe</li>
                        <li><i class="fas fa-check-circle"></i> <strong>Record in centimeters:</strong> Compare your measurement to our Philippines (CM) column</li>
                    </ul>
                </div>
            </div>

            <!-- Fit Guide Section -->
            <div class="guide-section">
                <h2><i class="fas fa-shoe-prints"></i> Fit Guide & Tips</h2>
                <div class="fit-guide">
                    <div class="fit-card">
                        <i class="fas fa-thumbs-up"></i>
                        <h4>Perfect Fit</h4>
                        <p>Your toes should have about a thumb's width of space from the front of the shoe</p>
                    </div>
                    <div class="fit-card">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h4>Too Small</h4>
                        <p>If your toes touch the front or feel cramped, consider going up half a size</p>
                    </div>
                    <div class="fit-card">
                        <i class="fas fa-arrows-alt-h"></i>
                        <h4>Width Matters</h4>
                        <p>Make sure the shoe doesn't feel tight across the widest part of your foot</p>
                    </div>
                    <div class="fit-card">
                        <i class="fas fa-clock"></i>
                        <h4>Break-in Period</h4>
                        <p>New shoes may feel slightly snug at first but should not cause discomfort</p>
                    </div>
                </div>
            </div>

            <!-- Back Button -->
            <div class="text-center">
                <a href="javascript:history.back()" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Product
                </a>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>


    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Global Cart and Favorites Scripts -->
    <script src="assets/js/global-cart.js"></script>
    <script src="assets/js/global-favorites.js"></script>

    <!-- Update badge counts on page load -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update cart and favorites counts if user is logged in
            if (typeof updateCartCountInNavbar === 'function') {
                // These functions will handle the badge updates
                fetch('get_cart_count.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateCartCountInNavbar(data.count);
                        }
                    })
                    .catch(error => console.log('Cart count update failed:', error));
            }

            if (typeof updateFavoritesCountInNavbar === 'function') {
                fetch('get_favorites_count.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateFavoritesCountInNavbar(data.count);
                        }
                    })
                    .catch(error => console.log('Favorites count update failed:', error));
            }
        });
    </script>
</body>
</html>