<?php
// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json');

try {
    // Include database connection
    require_once 'db.php';
    
    // Get search query from URL and sanitize
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8;
    
    // If no query, return empty results
    if (empty($query)) {
        echo json_encode([
            'success' => true,
            'query' => '',
            'results' => [],
            'count' => 0,
            'suggestions' => []
        ]);
        exit;
    }
    
    // Test database connection first
    if (!isset($pdo)) {
        throw new Exception('Database connection not available');
    }
    
    // ENHANCED SMART SEARCH - Comprehensive search across all relevant fields
    $searchTerm = strtolower(trim($query));
    $searchWords = array_filter(explode(' ', $searchTerm));
    $searchLength = strlen($searchTerm);

    // Build comprehensive search conditions with relevance scoring
    $searchConditions = [];
    $params = [];
    $paramCounter = 0;

    // SMART SEARCH: Enhanced logic for both single character and multi-character searches
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

        // TIER 1: Exact matches (highest priority)
        $searchConditions[] = "(
            LOWER(title) = :param_" . ++$paramCounter . " OR
            LOWER(brand) = :param_" . $paramCounter . " OR
            LOWER(category) = :param_" . $paramCounter . " OR
            LOWER(collection) = :param_" . $paramCounter . " OR
            LOWER(color) = :param_" . $paramCounter . "
        )";
        $params["param_$paramCounter"] = $searchTerm;

        // TIER 2: Starts with query (high priority)
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

        // TIER 3: Contains query (medium priority)
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

        // TIER 4: Individual word matches (for multi-word queries)
        foreach ($searchWords as $word) {
            if (strlen($word) > 1) {
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

        // TIER 4.5: Partial word matching for short queries (2-3 characters)
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

        // TIER 5: INTELLIGENT COLOR-SPECIFIC DETECTION (Exact database colors)
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

        // TIER 6: INTELLIGENT CATEGORY DETECTION (Exact database categories)
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

        // TIER 7: INTELLIGENT SIZE DETECTION (Exact database values)
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

        // TIER 8: BRAND AND COLLECTION DETECTION
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

    // Build enhanced COMBINATION RELEVANCE scoring for ordering
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

    // Execute the comprehensive search query
    if (!empty($searchConditions)) {
        $whereClause = "(" . implode(' OR ', $searchConditions) . ")";

        $sql = "SELECT
                    id, title, price, image, category, brand, color, height, width, collection, description, stock,
                    $relevanceCase as relevance_score
                FROM products
                WHERE $whereClause
                GROUP BY id
                ORDER BY relevance_score DESC,
                         CASE WHEN stock > 0 THEN 1 ELSE 0 END DESC,
                         price ASC,
                         title ASC
                LIMIT $limit";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $results = [];
    }
    
    // Enhanced suggestions based on search results
    $suggestions = [];
    if (count($results) > 0) {
        $categories = array_unique(array_filter(array_column($results, 'category')));
        $brands = array_unique(array_filter(array_column($results, 'brand')));
        $colors = array_unique(array_filter(array_column($results, 'color')));
        $collections = array_unique(array_filter(array_column($results, 'collection')));

        // Add category suggestions
        foreach (array_slice($categories, 0, 2) as $cat) {
            $suggestions[] = ucfirst(str_replace(['women', 'men', 'kids'], ['Women', 'Men', 'Kids'], $cat));
        }

        // Add brand suggestions
        foreach (array_slice($brands, 0, 2) as $brand) {
            if ($brand !== 'Generic') {
                $suggestions[] = ucfirst($brand) . ' shoes';
            }
        }

        // Add color suggestions
        foreach (array_slice($colors, 0, 2) as $color) {
            $suggestions[] = $color . ' shoes';
        }

        // Add collection suggestions
        foreach (array_slice($collections, 0, 1) as $collection) {
            if ($collection !== 'Standard') {
                $suggestions[] = $collection . ' collection';
            }
        }
    } else {
        // Fallback suggestions when no results found
        $suggestions = [
            'Running shoes',
            'Sneakers',
            'Black shoes',
            'White shoes',
            'Athletic shoes',
            'Casual shoes'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'results' => $results,
        'count' => count($results),
        'suggestions' => array_slice($suggestions, 0, 4)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage(),
        'results' => [],
        'count' => 0,
        'suggestions' => [],
        'debug' => [
            'error_type' => 'PDOException',
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'query' => $query ?? 'unknown'
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Search service temporarily unavailable: ' . $e->getMessage(),
        'results' => [],
        'count' => 0,
        'suggestions' => [],
        'debug' => [
            'error_type' => 'Exception',
            'error_message' => $e->getMessage(),
            'query' => $query ?? 'unknown'
        ]
    ]);
}
?>