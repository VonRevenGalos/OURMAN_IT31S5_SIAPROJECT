<?php
require_once 'includes/session.php';
require_once 'db.php';

// Get search query and sanitize
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$sort = $_GET['sort'] ?? 'relevance';
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$color = $_GET['color'] ?? '';
$price_min = $_GET['price_min'] ?? '';
$price_max = $_GET['price_max'] ?? '';
$from_dropdown = isset($_GET['from_dropdown']) ? true : false;

// If no search query, redirect to homepage
if (empty($query)) {
    header("Location: index.php");
    exit();
}

try {
    // Check database connection first
    if (!isset($pdo)) {
        throw new Exception('Database connection not available');
    }

    // Enhanced smart search algorithm with duplicate prevention
    $searchTerm = strtolower($query);
    $searchWords = array_filter(explode(' ', $searchTerm));
    $searchLength = strlen($searchTerm);
    
    // Build comprehensive search conditions with improved relevance
    $searchConditions = [];
    $params = [];
    $paramCounter = 0;
    
    // SMART SEARCH: Enhanced logic for both single character and multi-character searches (matching search.php exactly)
    if ($searchLength == 1) {
        // Single letter search - SMART approach: look for letter anywhere in key fields

        // PRIORITY 1: Starts with the letter (highest relevance)
        $searchConditions[] = "(
            LOWER(SUBSTRING(title, 1, 1)) = :param_" . ++$paramCounter . " OR
            LOWER(SUBSTRING(brand, 1, 1)) = :param_" . $paramCounter . " OR
            LOWER(SUBSTRING(color, 1, 1)) = :param_" . $paramCounter . " OR
            LOWER(SUBSTRING(category, 1, 1)) = :param_" . $paramCounter . "
        )";
        $params["param_$paramCounter"] = $searchTerm;

        // PRIORITY 2: Contains the letter anywhere (medium relevance)
        $searchConditions[] = "(
            LOWER(title) LIKE :param_" . ++$paramCounter . " OR
            LOWER(brand) LIKE :param_" . $paramCounter . " OR
            LOWER(color) LIKE :param_" . $paramCounter . " OR
            LOWER(category) LIKE :param_" . $paramCounter . " OR
            LOWER(collection) LIKE :param_" . $paramCounter . " OR
            LOWER(description) LIKE :param_" . $paramCounter . "
        )";
        $params["param_$paramCounter"] = '%' . $searchTerm . '%';

        // PRIORITY 3: Special single-letter mappings for common searches
        $singleLetterMappings = [
            'r' => ['running', 'red', 'regular'],
            'b' => ['black', 'blue', 'brown'],
            'w' => ['white', 'wide', 'women'],
            'g' => ['green', 'gray', 'grey'],
            'p' => ['pink', 'purple'],
            'y' => ['yellow'],
            'm' => ['men', 'multi', 'mid'],
            'h' => ['high'],
            'l' => ['low'],
            's' => ['sneakers', 'standard'],
            'a' => ['athletics', 'air'],
            'x' => ['xrizz', 'extra']
        ];

        if (isset($singleLetterMappings[$searchTerm])) {
            foreach ($singleLetterMappings[$searchTerm] as $mapping) {
                $searchConditions[] = "(
                    LOWER(title) LIKE :param_" . ++$paramCounter . " OR
                    LOWER(brand) LIKE :param_" . $paramCounter . " OR
                    LOWER(color) LIKE :param_" . $paramCounter . " OR
                    LOWER(category) LIKE :param_" . $paramCounter . " OR
                    LOWER(collection) LIKE :param_" . $paramCounter . " OR
                    LOWER(height) LIKE :param_" . $paramCounter . " OR
                    LOWER(width) LIKE :param_" . $paramCounter . "
                )";
                $params["param_$paramCounter"] = '%' . $mapping . '%';
            }
        }
    } else {
        // Multi-character search with enhanced logic
        
        // TIER 1: Exact matches (highest priority) - matching search.php exactly
        $searchConditions[] = "(
            LOWER(title) = :param_" . ++$paramCounter . " OR
            LOWER(brand) = :param_" . $paramCounter . " OR
            LOWER(category) = :param_" . $paramCounter . " OR
            LOWER(collection) = :param_" . $paramCounter . " OR
            LOWER(color) = :param_" . $paramCounter . "
        )";
        $params["param_$paramCounter"] = $searchTerm;

        // TIER 2: Starts with query (high priority) - matching search.php exactly
        $searchConditions[] = "(
            LOWER(title) LIKE :param_" . ++$paramCounter . " OR
            LOWER(brand) LIKE :param_" . $paramCounter . " OR
            LOWER(category) LIKE :param_" . $paramCounter . " OR
            LOWER(collection) LIKE :param_" . $paramCounter . " OR
            LOWER(color) LIKE :param_" . $paramCounter . " OR
            LOWER(height) LIKE :param_" . $paramCounter . " OR
            LOWER(width) LIKE :param_" . $paramCounter . "
        )";
        $params["param_$paramCounter"] = $searchTerm . '%';
        
        // TIER 3: Contains query (medium priority) - include description for comprehensive search
        $searchConditions[] = "(
            LOWER(title) LIKE :param_" . ++$paramCounter . " OR
            LOWER(brand) LIKE :param_" . $paramCounter . " OR
            LOWER(category) LIKE :param_" . $paramCounter . " OR
            LOWER(collection) LIKE :param_" . $paramCounter . " OR
            LOWER(description) LIKE :param_" . $paramCounter . " OR
            LOWER(color) LIKE :param_" . $paramCounter . " OR
            LOWER(height) LIKE :param_" . $paramCounter . " OR
            LOWER(width) LIKE :param_" . $paramCounter . "
        )";
        $params["param_$paramCounter"] = '%' . $searchTerm . '%';

        // TIER 4: Individual word matches (for multi-word queries) - comprehensive field search
        foreach ($searchWords as $index => $word) {
            if (strlen($word) > 1) { // Only words with 2+ characters
                $searchConditions[] = "(
                    LOWER(title) LIKE :param_" . ++$paramCounter . " OR
                    LOWER(brand) LIKE :param_" . $paramCounter . " OR
                    LOWER(category) LIKE :param_" . $paramCounter . " OR
                    LOWER(collection) LIKE :param_" . $paramCounter . " OR
                    LOWER(description) LIKE :param_" . $paramCounter . " OR
                    LOWER(color) LIKE :param_" . $paramCounter . " OR
                    LOWER(height) LIKE :param_" . $paramCounter . " OR
                    LOWER(width) LIKE :param_" . $paramCounter . "
                )";
                $params["param_$paramCounter"] = '%' . $word . '%';
            }
        }

        // TIER 4.5: Partial word matching for short queries (2-3 characters) - matching search.php exactly
        if ($searchLength >= 2 && $searchLength <= 4) {
            // Add partial matching for common short terms
            $partialMatches = [
                'run' => ['running', 'runner'],
                'ath' => ['athletics', 'athletic'],
                'sne' => ['sneakers', 'sneaker'],
                'wom' => ['women', 'woman'],
                'kid' => ['kids', 'children'],
                'men' => ['mens'],
                'pro' => ['professional'],
                'max' => ['maximum'],
                'air' => ['aero'],
                'riz' => ['rizz'],
                'vel' => ['velocity'],
                'pow' => ['power'],
                'spe' => ['speed'],
                'fle' => ['flex', 'flexible'],
                'ult' => ['ultra'],
                'viv' => ['viva'],
                'lun' => ['luna'],
                'urb' => ['urban']
            ];

            foreach ($partialMatches as $partial => $fullWords) {
                if (strpos($searchTerm, $partial) !== false) {
                    foreach ($fullWords as $fullWord) {
                        $searchConditions[] = "(
                            LOWER(title) LIKE :param_" . ++$paramCounter . " OR
                            LOWER(brand) LIKE :param_" . $paramCounter . " OR
                            LOWER(category) LIKE :param_" . $paramCounter . " OR
                            LOWER(collection) LIKE :param_" . $paramCounter . " OR
                            LOWER(description) LIKE :param_" . $paramCounter . "
                        )";
                        $params["param_$paramCounter"] = '%' . $fullWord . '%';
                    }
                }
            }
        }

        // TIER 5: INTELLIGENT COLOR-SPECIFIC DETECTION (Exact database colors) - matching search.php exactly
        $exactColors = ['Black', 'Blue', 'Brown', 'Green', 'Gray', 'Multi-Colour', 'Orange', 'Pink', 'Purple', 'Red', 'White', 'Yellow'];
        $colorDetected = false;

        // Enhanced color mapping with exact database values
        $colorMap = [
            // Exact color names (highest priority)
            'black' => 'Black', 'blk' => 'Black', 'dark' => 'Black',
            'blue' => 'Blue', 'blu' => 'Blue', 'navy' => 'Blue',
            'brown' => 'Brown', 'brn' => 'Brown', 'tan' => 'Brown',
            'green' => 'Green', 'grn' => 'Green',
            'gray' => 'Gray', 'grey' => 'Gray', 'gry' => 'Gray',
            'multi-colour' => 'Multi-Colour', 'multi' => 'Multi-Colour', 'multicolor' => 'Multi-Colour', 'multicolour' => 'Multi-Colour',
            'orange' => 'Orange', 'org' => 'Orange',
            'pink' => 'Pink', 'pnk' => 'Pink',
            'purple' => 'Purple', 'prpl' => 'Purple', 'violet' => 'Purple',
            'red' => 'Red', 'rd' => 'Red',
            'white' => 'White', 'wht' => 'White', 'light' => 'White',
            'yellow' => 'Yellow', 'ylw' => 'Yellow', 'gold' => 'Yellow'
        ];

        // Check for exact color matches first (highest priority)
        foreach ($colorMap as $keyword => $color) {
            if ($searchTerm === $keyword || in_array($keyword, $searchWords)) {
                $searchConditions[] = "color = :param_" . ++$paramCounter;
                $params["param_$paramCounter"] = $color;
                $colorDetected = true;
                break;
            }
        }

        // If no exact match, check for partial color matches
        if (!$colorDetected) {
            foreach ($colorMap as $keyword => $color) {
                if (strpos($searchTerm, $keyword) !== false) {
                    $searchConditions[] = "color = :param_" . ++$paramCounter;
                    $params["param_$paramCounter"] = $color;
                    $colorDetected = true;
                    break;
                }
            }
        }

        // Single letter color detection (only if no other color detected)
        if (!$colorDetected && $searchLength == 1) {
            $singleLetterColors = [
                'b' => 'Black',  // Most common black
                'r' => 'Red',
                'g' => 'Green',
                'w' => 'White',
                'y' => 'Yellow',
                'p' => 'Pink',   // Most common pink
                'o' => 'Orange'
            ];

            if (isset($singleLetterColors[$searchTerm])) {
                $searchConditions[] = "color = :param_" . ++$paramCounter;
                $params["param_$paramCounter"] = $singleLetterColors[$searchTerm];
                $colorDetected = true;
            }
        }

        // TIER 6: INTELLIGENT CATEGORY DETECTION (Exact database categories) - matching search.php exactly
        $exactCategories = ['sneakers', 'running', 'athletics', 'womenathletics', 'womenrunning', 'womensneakers'];
        $categoryDetected = false;

        $categoryMap = [
            // Exact category matches
            'sneakers' => 'sneakers', 'sneaker' => 'sneakers', 'sneak' => 'sneakers',
            'running' => ['running', 'womenrunning'], 'run' => ['running', 'womenrunning'], 'runner' => ['running', 'womenrunning'],
            'athletics' => ['athletics', 'womenathletics'], 'athletic' => ['athletics', 'womenathletics'], 'ath' => ['athletics', 'womenathletics'],
            'women' => ['womenathletics', 'womenrunning', 'womensneakers'], 'woman' => ['womenathletics', 'womenrunning', 'womensneakers'],
            'womens' => ['womenathletics', 'womenrunning', 'womensneakers'], 'female' => ['womenathletics', 'womenrunning', 'womensneakers']
        ];

        foreach ($categoryMap as $keyword => $categories) {
            if ($searchTerm === $keyword || in_array($keyword, $searchWords) || strpos($searchTerm, $keyword) !== false) {
                if (is_array($categories)) {
                    $categoryConditions = [];
                    foreach ($categories as $cat) {
                        $categoryConditions[] = "category = :param_" . ++$paramCounter;
                        $params["param_$paramCounter"] = $cat;
                    }
                    $searchConditions[] = "(" . implode(' OR ', $categoryConditions) . ")";
                } else {
                    $searchConditions[] = "category = :param_" . ++$paramCounter;
                    $params["param_$paramCounter"] = $categories;
                }
                $categoryDetected = true;
                break;
            }
        }

        // TIER 7: INTELLIGENT SIZE DETECTION (Exact database values) - matching search.php exactly
        $heightMap = [
            'high' => 'high top', 'hightop' => 'high top', 'hi' => 'high top', 'high top' => 'high top',
            'low' => 'low top', 'lowtop' => 'low top', 'lo' => 'low top', 'low top' => 'low top',
            'mid' => 'mid top', 'midtop' => 'mid top', 'medium' => 'mid top', 'mid top' => 'mid top'
        ];

        $widthMap = [
            'wide' => 'wide', 'w' => 'wide',
            'regular' => 'regular', 'reg' => 'regular', 'normal' => 'regular',
            'extra' => 'extra wide', 'extrawide' => 'extra wide', 'extra wide' => 'extra wide'
        ];

        // Height detection
        foreach ($heightMap as $keyword => $height) {
            if ($searchTerm === $keyword || in_array($keyword, $searchWords) || strpos($searchTerm, $keyword) !== false) {
                $searchConditions[] = "height = :param_" . ++$paramCounter;
                $params["param_$paramCounter"] = $height;
                break;
            }
        }

        // Width detection
        foreach ($widthMap as $keyword => $width) {
            if ($searchTerm === $keyword || in_array($keyword, $searchWords) || strpos($searchTerm, $keyword) !== false) {
                $searchConditions[] = "width = :param_" . ++$paramCounter;
                $params["param_$paramCounter"] = $width;
                break;
            }
        }

        // TIER 8: BRAND AND COLLECTION DETECTION - matching search.php exactly
        $brandMap = [
            'xrizz' => 'XRizz', 'x rizz' => 'XRizz', 'rizz' => ['XRizz', 'Generic Rizz'],
            'generic' => ['Generic', 'Generic Rizz']
        ];

        foreach ($brandMap as $keyword => $brands) {
            if ($searchTerm === $keyword || in_array($keyword, $searchWords) || strpos($searchTerm, $keyword) !== false) {
                if (is_array($brands)) {
                    $brandConditions = [];
                    foreach ($brands as $brand) {
                        $brandConditions[] = "brand = :param_" . ++$paramCounter;
                        $params["param_$paramCounter"] = $brand;
                    }
                    $searchConditions[] = "(" . implode(' OR ', $brandConditions) . ")";
                } else {
                    $searchConditions[] = "brand = :param_" . ++$paramCounter;
                    $params["param_$paramCounter"] = $brands;
                }
                break;
            }
        }

        $collectionMap = [
            'air' => 'Air Rizz', 'air rizz' => 'Air Rizz',
            'standard' => 'Standard'
        ];

        foreach ($collectionMap as $keyword => $collection) {
            if ($searchTerm === $keyword || in_array($keyword, $searchWords) || strpos($searchTerm, $keyword) !== false) {
                $searchConditions[] = "collection = :param_" . ++$paramCounter;
                $params["param_$paramCounter"] = $collection;
                break;
            }
        }

    }
    
    // Add additional filters
    $additionalFilters = [];
    if (!empty($category)) {
        $additionalFilters[] = "LOWER(category) = :filter_category";
        $params['filter_category'] = strtolower($category);
    }
    if (!empty($brand)) {
        $additionalFilters[] = "LOWER(brand) = :filter_brand";
        $params['filter_brand'] = strtolower($brand);
    }
    if (!empty($color)) {
        $additionalFilters[] = "LOWER(color) = :filter_color";
        $params['filter_color'] = strtolower($color);
    }
    if (!empty($price_min) && is_numeric($price_min)) {
        $additionalFilters[] = "price >= :price_min";
        $params['price_min'] = $price_min;
    }
    if (!empty($price_max) && is_numeric($price_max)) {
        $additionalFilters[] = "price <= :price_max";
        $params['price_max'] = $price_max;
    }
    
    // Build enhanced COMBINATION RELEVANCE scoring (matching search.php exactly)
    $relevanceCase = $searchLength == 1 ?
        "CASE
            WHEN LOWER(SUBSTRING(title, 1, 1)) = '" . $searchTerm . "' THEN 1000
            WHEN LOWER(SUBSTRING(brand, 1, 1)) = '" . $searchTerm . "' THEN 950
            WHEN LOWER(SUBSTRING(color, 1, 1)) = '" . $searchTerm . "' THEN 900
            WHEN LOWER(SUBSTRING(category, 1, 1)) = '" . $searchTerm . "' THEN 850
            WHEN LOWER(title) LIKE '%" . $searchTerm . "%' THEN 700
            WHEN LOWER(brand) LIKE '%" . $searchTerm . "%' THEN 650
            WHEN LOWER(category) LIKE '%" . $searchTerm . "%' THEN 600
            WHEN LOWER(collection) LIKE '%" . $searchTerm . "%' THEN 550
            WHEN LOWER(description) LIKE '%" . $searchTerm . "%' THEN 400
            WHEN LOWER(height) LIKE '%" . $searchTerm . "%' THEN 350
            WHEN LOWER(width) LIKE '%" . $searchTerm . "%' THEN 350
            ELSE 100
        END" :
        "CASE
            -- COMBINATION RELEVANCE: Multiple field matches get bonus points
            WHEN (LOWER(title) LIKE '%" . $searchTerm . "%' AND LOWER(category) LIKE '%" . $searchTerm . "%') THEN 1200
            WHEN (LOWER(title) LIKE '%" . $searchTerm . "%' AND LOWER(color) LIKE '%" . $searchTerm . "%') THEN 1150
            WHEN (LOWER(category) LIKE '%" . $searchTerm . "%' AND LOWER(color) LIKE '%" . $searchTerm . "%') THEN 1100

            -- EXACT MATCHES (Highest single-field priority)
            WHEN LOWER(title) = '" . $searchTerm . "' THEN 1000
            WHEN LOWER(brand) = '" . $searchTerm . "' THEN 950
            WHEN LOWER(category) = '" . $searchTerm . "' THEN 900
            WHEN LOWER(color) = '" . $searchTerm . "' THEN 850
            WHEN LOWER(collection) = '" . $searchTerm . "' THEN 800
            WHEN LOWER(height) = '" . $searchTerm . "' THEN 750
            WHEN LOWER(width) = '" . $searchTerm . "' THEN 750

            -- STARTS WITH MATCHES
            WHEN LOWER(title) LIKE '" . $searchTerm . "%' THEN 700
            WHEN LOWER(brand) LIKE '" . $searchTerm . "%' THEN 650
            WHEN LOWER(category) LIKE '" . $searchTerm . "%' THEN 600
            WHEN LOWER(collection) LIKE '" . $searchTerm . "%' THEN 550

            -- CONTAINS MATCHES
            WHEN LOWER(title) LIKE '%" . $searchTerm . "%' THEN 500
            WHEN LOWER(brand) LIKE '%" . $searchTerm . "%' THEN 450
            WHEN LOWER(category) LIKE '%" . $searchTerm . "%' THEN 400
            WHEN LOWER(collection) LIKE '%" . $searchTerm . "%' THEN 350
            WHEN LOWER(description) LIKE '%" . $searchTerm . "%' THEN 300
            WHEN LOWER(color) LIKE '%" . $searchTerm . "%' THEN 250
            WHEN LOWER(height) LIKE '%" . $searchTerm . "%' THEN 200
            WHEN LOWER(width) LIKE '%" . $searchTerm . "%' THEN 200
            ELSE 50
        END";
    
    // Build ORDER BY clause based on sort option
    $orderBy = "title ASC";
    switch ($sort) {
        case 'price_low':
            $orderBy = "price ASC, stock DESC, title ASC";
            break;
        case 'price_high':
            $orderBy = "price DESC, stock DESC, title ASC";
            break;
        case 'az':
            $orderBy = "title ASC, stock DESC, price ASC";
            break;
        case 'newness':
            $orderBy = "date_added DESC, stock DESC, price ASC";
            break;
        case 'relevance':
        default:
            $orderBy = "$relevanceCase DESC,
            CASE WHEN stock > 0 THEN 1 ELSE 0 END DESC,
            price ASC,
            title ASC";
            break;
    }
    
    // Build the main query with GROUP BY to eliminate duplicates
    // Check if we have search conditions
    if (empty($searchConditions)) {
        // If no search conditions were built, create a basic search
        $searchConditions[] = "(
            LOWER(title) LIKE :basic_search OR
            LOWER(brand) LIKE :basic_search OR
            LOWER(category) LIKE :basic_search OR
            LOWER(description) LIKE :basic_search OR
            LOWER(color) LIKE :basic_search
        )";
        $params['basic_search'] = '%' . $searchTerm . '%';
    }

    $whereClause = "(" . implode(' OR ', $searchConditions) . ")";
    if (!empty($additionalFilters)) {
        $whereClause .= " AND " . implode(' AND ', $additionalFilters);
    }

    $sql = "SELECT
                id,
                title,
                price,
                image,
                category,
                brand,
                color,
                height,
                width,
                collection,
                description,
                stock,
                date_added,
                $relevanceCase as relevance_score
            FROM products
            WHERE $whereClause
            GROUP BY id
            ORDER BY " . $orderBy;

    // Debug output if requested
    if (isset($_GET['debug'])) {
        echo "<pre>Search Term: " . htmlspecialchars($searchTerm) . "</pre>";
        echo "<pre>Search Length: " . $searchLength . "</pre>";
        echo "<pre>Search Conditions: " . count($searchConditions) . "</pre>";
        echo "<pre>Parameters: " . print_r($params, true) . "</pre>";
        echo "<pre>WHERE Clause: " . htmlspecialchars($whereClause) . "</pre>";
        echo "<pre>SQL: " . htmlspecialchars($sql) . "</pre>";
        echo "<hr>";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug output for results
    if (isset($_GET['debug'])) {
        echo "<pre>Products found: " . count($products) . "</pre>";
        if (count($products) > 0) {
            echo "<pre>First product: " . print_r($products[0], true) . "</pre>";
        }
        echo "<hr>";
    }
    
    // If no results, try fuzzy search with individual words
    if (count($products) == 0 && count($searchWords) > 1) {
        $fuzzyConditions = [];
        $fuzzyParams = [];
        $fuzzyCounter = 0;
        
        foreach ($searchWords as $index => $word) {
            if (strlen($word) > 2) {
                $fuzzyConditions[] = "(
                    LOWER(title) LIKE :fuzzy_param_" . ++$fuzzyCounter . " OR
                    LOWER(brand) LIKE :fuzzy_param_" . $fuzzyCounter . " OR
                    LOWER(category) LIKE :fuzzy_param_" . $fuzzyCounter . "
                )";
                $fuzzyParams["fuzzy_param_$fuzzyCounter"] = '%' . $word . '%';
            }
        }
        
        if (!empty($fuzzyConditions)) {
            $fuzzyWhereClause = "(" . implode(' OR ', $fuzzyConditions) . ")";
            if (!empty($additionalFilters)) {
                $fuzzyWhereClause .= " AND " . implode(' AND ', $additionalFilters);
            }
            
            $fuzzySql = "SELECT 
                            id, 
                            title, 
                            price, 
                            image, 
                            category, 
                            brand, 
                            color, 
                            height,
                            width,
                            collection,
                            description,
                            stock,
                            date_added,
                            10 as relevance_score
                        FROM products 
                        WHERE $fuzzyWhereClause
                        GROUP BY id
                        ORDER BY 
                            CASE WHEN stock > 0 THEN 1 ELSE 0 END DESC,
                            price ASC,
                            title ASC";
            
            $fuzzyStmt = $pdo->prepare($fuzzySql);
            $fuzzyStmt->execute(array_merge($fuzzyParams, $params));
            $products = $fuzzyStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    
    // Clean image paths and ensure unique products
    $uniqueProducts = [];
    $seenIds = [];
    foreach ($products as $product) {
        if (!in_array($product['id'], $seenIds)) {
            $product['image'] = trim($product['image']);
            $uniqueProducts[] = $product;
            $seenIds[] = $product['id'];
        }
    }
    $products = $uniqueProducts;
    
} catch (PDOException $e) {
    $products = [];
    error_log("Search error: " . $e->getMessage());
    // Debug: Show error for testing
    if (isset($_GET['debug'])) {
        echo "<pre>SQL Error: " . $e->getMessage() . "</pre>";
        echo "<pre>Search Term: " . htmlspecialchars($searchTerm) . "</pre>";
        echo "<pre>Search Conditions: " . count($searchConditions) . "</pre>";
        echo "<pre>Parameters: " . print_r($params, true) . "</pre>";
        if (isset($sql)) {
            echo "<pre>SQL: " . htmlspecialchars($sql) . "</pre>";
        }
        exit;
    }
} catch (Exception $e) {
    $products = [];
    error_log("General error: " . $e->getMessage());
    // Debug: Show error for testing
    if (isset($_GET['debug'])) {
        echo "<pre>General Error: " . $e->getMessage() . "</pre>";
        echo "<pre>Search Term: " . htmlspecialchars($searchTerm) . "</pre>";
        echo "<pre>Search Conditions: " . count($searchConditions) . "</pre>";
        echo "<pre>Parameters: " . print_r($params, true) . "</pre>";
        exit;
    }
}

// Get current user
$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results for "<?php echo htmlspecialchars($query); ?>" - ShoeARizz</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/men.css">
    <link rel="stylesheet" href="assets/css/search.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="men-container">
        <div class="container-fluid">
            <!-- Search Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-search me-3"></i>Search Results
                </h1>
                <p class="page-subtitle">
                    <?php if (count($products) > 0): ?>
                        Found <strong><?php echo number_format(count($products)); ?></strong> result<?php echo count($products) !== 1 ? 's' : ''; ?> for
                        <span class="search-query-highlight">"<?php echo htmlspecialchars($query); ?>"</span>
                        <?php if ($from_dropdown): ?>
                            <br><small class="text-success">
                                <i class="fas fa-check-circle me-1"></i>
                                Showing all results from your search dropdown
                            </small>
                        <?php endif; ?>
                        <?php if (isset($_GET['debug'])): ?>
                            <br><small class="text-muted">Debug: Colors found: <?php echo implode(', ', array_unique(array_filter(array_column($products, 'color')))); ?></small>
                        <?php endif; ?>
                    <?php else: ?>
                        No results found for <span class="search-query-highlight">"<?php echo htmlspecialchars($query); ?>"</span>
                        <?php if ($from_dropdown): ?>
                            <br><small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                These are the complete results from your search dropdown
                            </small>
                        <?php endif; ?>
                    <?php endif; ?>
                </p>
            </div>
            
            <!-- Advanced Filters and Products Header -->
            <?php if (count($products) > 0): ?>
            <div class="row">
                <!-- Filters Sidebar -->
                <div class="col-lg-3 col-md-4" id="sidebarContainer">
                    <div class="filters-sidebar">
                        <div class="filters-header">
                            <h3 class="filters-title">Filters</h3>
                            <div class="filter-actions">
                                <button class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="filter-group">
                            <h4 class="filter-title">Category</h4>
                            <div class="filter-options">
                                <?php
                                // Get unique categories from search results only
                                $categories = array_unique(array_filter(array_column($products, 'category')));
                                if (!empty($categories)): 
                                    foreach ($categories as $cat): 
                                        if (!empty($cat)): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="cat_<?php echo htmlspecialchars($cat); ?>" 
                                                       value="<?php echo htmlspecialchars($cat); ?>"
                                                       <?php echo ($category === $cat) ? 'checked' : ''; ?>
                                                       onchange="applyFilters()">
                                                <label class="form-check-label" for="cat_<?php echo htmlspecialchars($cat); ?>">
                                                    <?php echo ucfirst(str_replace(['women', 'men', 'kids'], ['Women', 'Men', 'Kids'], htmlspecialchars($cat))); ?>
                                                </label>
                                            </div>
                                        <?php endif;
                                    endforeach;
                                else: ?>
                                    <div class="text-muted">No categories available</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Brand Filter -->
                        <div class="filter-group">
                            <h4 class="filter-title">Brand</h4>
                            <div class="filter-options">
                                <?php
                                // Get unique brands from search results only
                                $brands = array_unique(array_filter(array_column($products, 'brand')));
                                if (!empty($brands)): 
                                    foreach ($brands as $br): 
                                        if (!empty($br)): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="brand_<?php echo htmlspecialchars($br); ?>" 
                                                       value="<?php echo htmlspecialchars($br); ?>"
                                                       <?php echo ($brand === $br) ? 'checked' : ''; ?>
                                                       onchange="applyFilters()">
                                                <label class="form-check-label" for="brand_<?php echo htmlspecialchars($br); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($br)); ?>
                                                </label>
                                            </div>
                                        <?php endif;
                                    endforeach;
                                else: ?>
                                    <div class="text-muted">No brands available</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Color Filter -->
                        <div class="filter-group">
                            <h4 class="filter-title">Color</h4>
                            <div class="filter-options">
                                <?php
                                // Get unique colors from search results only
                                $colors = array_unique(array_filter(array_column($products, 'color')));
                                if (!empty($colors)): 
                                    foreach ($colors as $col): 
                                        if (!empty($col)): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="color_<?php echo htmlspecialchars($col); ?>" 
                                                       value="<?php echo htmlspecialchars($col); ?>"
                                                       <?php echo ($color === $col) ? 'checked' : ''; ?>
                                                       onchange="applyFilters()">
                                                <label class="form-check-label" for="color_<?php echo htmlspecialchars($col); ?>">
                                                    <span class="color-indicator" style="background-color: <?php echo strtolower(htmlspecialchars($col)); ?>;"></span>
                                                    <?php echo ucfirst(htmlspecialchars($col)); ?>
                                                </label>
                                            </div>
                                        <?php endif;
                                    endforeach;
                                else: ?>
                                    <div class="text-muted">No colors available</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Height Filter -->
                        <div class="filter-group">
                            <h4 class="filter-title">Height</h4>
                            <div class="filter-options">
                                <?php
                                // Get unique heights from search results only
                                $heights = array_unique(array_filter(array_column($products, 'height')));
                                if (!empty($heights)):
                                    foreach ($heights as $h):
                                        if (!empty($h)): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                       id="height_<?php echo htmlspecialchars($h); ?>"
                                                       value="<?php echo htmlspecialchars($h); ?>"
                                                       onchange="applyFilters()">
                                                <label class="form-check-label" for="height_<?php echo htmlspecialchars($h); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($h)); ?>
                                                </label>
                                            </div>
                                        <?php endif;
                                    endforeach;
                                else: ?>
                                    <div class="text-muted">No heights available</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Width Filter -->
                        <div class="filter-group">
                            <h4 class="filter-title">Width</h4>
                            <div class="filter-options">
                                <?php
                                // Get unique widths from search results only
                                $widths = array_unique(array_filter(array_column($products, 'width')));
                                if (!empty($widths)):
                                    foreach ($widths as $w):
                                        if (!empty($w)): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                       id="width_<?php echo htmlspecialchars($w); ?>"
                                                       value="<?php echo htmlspecialchars($w); ?>"
                                                       onchange="applyFilters()">
                                                <label class="form-check-label" for="width_<?php echo htmlspecialchars($w); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($w)); ?>
                                                </label>
                                            </div>
                                        <?php endif;
                                    endforeach;
                                else: ?>
                                    <div class="text-muted">No widths available</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Collection Filter -->
                        <div class="filter-group">
                            <h4 class="filter-title">Collection</h4>
                            <div class="filter-options">
                                <?php
                                // Get unique collections from search results only
                                $collections = array_unique(array_filter(array_column($products, 'collection')));
                                if (!empty($collections)):
                                    foreach ($collections as $coll):
                                        if (!empty($coll) && $coll !== 'Standard'): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                       id="collection_<?php echo htmlspecialchars($coll); ?>"
                                                       value="<?php echo htmlspecialchars($coll); ?>"
                                                       onchange="applyFilters()">
                                                <label class="form-check-label" for="collection_<?php echo htmlspecialchars($coll); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($coll)); ?>
                                                </label>
                                            </div>
                                        <?php endif;
                                    endforeach;
                                else: ?>
                                    <div class="text-muted">No collections available</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Price Range Filter -->
                        <div class="filter-group">
                            <h4 class="filter-title">Price Range</h4>
                            <div class="price-range-container">
                                <?php
                                // Calculate dynamic price range from search results
                                $prices = array_column($products, 'price');
                                $minPrice = !empty($prices) ? min($prices) : 0;
                                $maxPrice = !empty($prices) ? max($prices) : 10000;
                                $currentMin = $price_min ?: $minPrice;
                                $currentMax = $price_max ?: $maxPrice;
                                ?>
                                <div class="price-display">
                                    <span>₱<span id="priceMinDisplay"><?php echo number_format($currentMin); ?></span></span>
                                    <span>₱<span id="priceMaxDisplay"><?php echo number_format($currentMax); ?></span></span>
                                </div>
                                <div class="dual-range-slider">
                                    <input type="range" min="<?php echo $minPrice; ?>" max="<?php echo $maxPrice; ?>" step="50" 
                                           value="<?php echo $currentMin; ?>" 
                                           id="priceMin" oninput="updatePriceRange()">
                                    <input type="range" min="<?php echo $minPrice; ?>" max="<?php echo $maxPrice; ?>" step="50" 
                                           value="<?php echo $currentMax; ?>" 
                                           id="priceMax" oninput="updatePriceRange()">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Products Container -->
                <div class="col-lg-9 col-md-8" id="productsContainer">
                    <div class="products-header">
                        <div class="results-info">
                            <span class="results-count"><?php echo count($products); ?> products found</span>
                        </div>
                        <div class="sort-controls">
                            <button class="btn btn-outline-secondary d-lg-none me-2" onclick="toggleFilters()">
                                <i class="fas fa-filter"></i> Filters
                            </button>
                            <select class="form-select" id="sortSelect" onchange="applySort()">
                                <option value="relevance" <?php echo ($sort === 'relevance') ? 'selected' : ''; ?>>Most Relevant</option>
                                <option value="price_low" <?php echo ($sort === 'price_low') ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo ($sort === 'price_high') ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="az" <?php echo ($sort === 'az') ? 'selected' : ''; ?>>A-Z</option>
                                <option value="newness" <?php echo ($sort === 'newness') ? 'selected' : ''; ?>>Newest First</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Products Grid -->
                    <div class="products-grid">
                        <div class="row">
                            <?php foreach ($products as $product): ?>
                                <div class="col-lg-4 col-md-6 col-sm-6 mb-4">
                                    <div class="product-card h-100">
                                        <div class="product-image">
                                            <a href="product.php?id=<?php echo $product['id']; ?>">
                                                <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($product['title']); ?>" 
                                                     class="img-fluid"
                                                     onerror="this.src='assets/img/placeholder.jpg'">
                                            </a>
                                            <div class="product-overlay">
                                                <button class="btn btn-outline-light btn-sm" onclick="addToFavorites(<?php echo $product['id']; ?>)">
                                                    <i class="fas fa-heart"></i>
                                                </button>
                                                <button class="btn btn-outline-light btn-sm" onclick="quickView(<?php echo $product['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="product-info">
                                            <div class="product-brand"><?php echo htmlspecialchars($product['brand'] ?? 'Generic'); ?></div>
                                            <h3 class="product-title">
                                                <a href="product.php?id=<?php echo $product['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($product['title']); ?>
                                                </a>
                                            </h3>
                                            <div class="product-details">
                                                <span class="product-color"><?php echo htmlspecialchars($product['color'] ?? 'N/A'); ?></span>
                                                <span class="product-height"><?php echo ucfirst(htmlspecialchars($product['height'] ?? 'N/A')); ?></span>
                                            </div>
                                            <div class="product-details">
                                                <span class="product-width"><?php echo ucfirst(htmlspecialchars($product['width'] ?? 'N/A')); ?></span>
                                                <span class="product-collection"><?php echo htmlspecialchars($product['collection'] ?? 'N/A'); ?></span>
                                            </div>
                                            <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                                            <div class="product-stock">
                                                <?php if ($product['stock'] > 0): ?>
                                                    <span class="text-success">
                                                        <i class="fas fa-check-circle me-1"></i>In Stock
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-danger">
                                                        <i class="fas fa-times-circle me-1"></i>Out of Stock
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (isLoggedIn()): ?>
                                                <button class="btn btn-primary w-100 mt-2"
                                                        onclick="addToCart(<?php echo $product['id']; ?>, 1, null, this)"
                                                        <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                                                </button>
                                            <?php else: ?>
                                                <a href="signup.php" class="btn btn-primary w-100 mt-2">
                                                    <i class="fas fa-user-plus me-2"></i>Sign Up to Add to Cart
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- No Results Message -->
            <?php if (empty($products)): ?>
                <div class="col-12">
                    <div class="no-products text-center py-5">
                        <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                        <h3>No products found</h3>
                        <p class="text-muted">Try adjusting your search terms or filters.</p>
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/navbar.js"></script>
    <!-- Search JS is already included in navbar.php -->
    <?php if (isLoggedIn()): ?>
    <script src="assets/js/global-cart.js"></script>
    <script src="assets/js/global-favorites.js"></script>
    <?php endif; ?>
    <script src="assets/js/men.js"></script>
    
    <script>
        // Sort functionality
        function applySort() {
            const sortSelect = document.getElementById('sortSelect');
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('sort', sortSelect.value);
            window.location.href = currentUrl.toString();
        }
        
        // Filter functionality
        function applyFilters() {
            const currentUrl = new URL(window.location);
            
            // Get selected filters
            const selectedCategories = Array.from(document.querySelectorAll('input[id^="cat_"]:checked')).map(cb => cb.value);
            const selectedBrands = Array.from(document.querySelectorAll('input[id^="brand_"]:checked')).map(cb => cb.value);
            const selectedColors = Array.from(document.querySelectorAll('input[id^="color_"]:checked')).map(cb => cb.value);
            const priceMin = document.getElementById('priceMin').value;
            const priceMax = document.getElementById('priceMax').value;
            
            // Update URL parameters
            if (selectedCategories.length > 0) {
                currentUrl.searchParams.set('category', selectedCategories[0]); // Take first selected
            } else {
                currentUrl.searchParams.delete('category');
            }
            
            if (selectedBrands.length > 0) {
                currentUrl.searchParams.set('brand', selectedBrands[0]); // Take first selected
            } else {
                currentUrl.searchParams.delete('brand');
            }
            
            if (selectedColors.length > 0) {
                currentUrl.searchParams.set('color', selectedColors[0]); // Take first selected
            } else {
                currentUrl.searchParams.delete('color');
            }
            
            // Get the min/max values from the slider attributes
            const priceMinSlider = document.getElementById('priceMin');
            const priceMaxSlider = document.getElementById('priceMax');
            const minRange = priceMinSlider.getAttribute('min');
            const maxRange = priceMaxSlider.getAttribute('max');
            
            if (priceMin && priceMin !== minRange) {
                currentUrl.searchParams.set('price_min', priceMin);
            } else {
                currentUrl.searchParams.delete('price_min');
            }
            
            if (priceMax && priceMax !== maxRange) {
                currentUrl.searchParams.set('price_max', priceMax);
            } else {
                currentUrl.searchParams.delete('price_max');
            }
            
            window.location.href = currentUrl.toString();
        }
        
        // Clear all filters
        function clearFilters() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.delete('category');
            currentUrl.searchParams.delete('brand');
            currentUrl.searchParams.delete('color');
            currentUrl.searchParams.delete('price_min');
            currentUrl.searchParams.delete('price_max');
            window.location.href = currentUrl.toString();
        }
        
        // Update price range display
        function updatePriceRange() {
            const priceMin = document.getElementById('priceMin').value;
            const priceMax = document.getElementById('priceMax').value;
            
            // Format numbers with commas
            document.getElementById('priceMinDisplay').textContent = parseInt(priceMin).toLocaleString();
            document.getElementById('priceMaxDisplay').textContent = parseInt(priceMax).toLocaleString();
            
            // Apply filters after a short delay
            clearTimeout(window.priceFilterTimeout);
            window.priceFilterTimeout = setTimeout(applyFilters, 500);
        }
        
        // Toggle filters on mobile
        function toggleFilters() {
            const sidebar = document.getElementById('sidebarContainer');
            const productsContainer = document.getElementById('productsContainer');
            
            if (sidebar.style.display === 'none' || sidebar.style.display === '') {
                sidebar.style.display = 'block';
                productsContainer.classList.remove('col-lg-9', 'col-md-8');
                productsContainer.classList.add('col-12');
            } else {
                sidebar.style.display = 'none';
                productsContainer.classList.remove('col-12');
                productsContainer.classList.add('col-lg-9', 'col-md-8');
            }
        }
        
        // Cart functionality is handled by global-cart.js
        
        // Add to favorites functionality is handled by global-favorites.js
        
        // Quick view functionality
        function quickView(productId) {
            // Simple quick view functionality - you can enhance this
            window.location.href = 'product.php?id=' + productId;
        }
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Add search analytics tracking
            if (typeof gtag !== 'undefined') {
                gtag('event', 'search', {
                    'search_term': '<?php echo addslashes($query); ?>',
                    'result_count': <?php echo count($products); ?>
                });
            }
            
            // Hide filters on mobile by default
            if (window.innerWidth < 992) {
                const sidebar = document.getElementById('sidebarContainer');
                if (sidebar) {
                    sidebar.style.display = 'none';
                }
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 992) {
                    const sidebar = document.getElementById('sidebarContainer');
                    const productsContainer = document.getElementById('productsContainer');
                    if (sidebar && productsContainer) {
                        sidebar.style.display = 'block';
                        productsContainer.classList.remove('col-12');
                        productsContainer.classList.add('col-lg-9', 'col-md-8');
                    }
                }
            });
        });
    </script>
</body>
</html>
