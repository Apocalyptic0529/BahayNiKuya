<?php
$pageTitle = "Home";
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

// Get featured properties
$featuredProperties = getProperties(['featured' => true, 'limit' => 6]);

// Get filter values
$minPrice = isset($_GET['min_price']) ? $_GET['min_price'] : '';
$maxPrice = isset($_GET['max_price']) ? $_GET['max_price'] : '';
$bedrooms = isset($_GET['bedrooms']) ? $_GET['bedrooms'] : '';
$bathrooms = isset($_GET['bathrooms']) ? $_GET['bathrooms'] : '';
$propertyType = isset($_GET['property_type']) ? $_GET['property_type'] : '';
$city = isset($_GET['city']) ? $_GET['city'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Apply filters if any
$filters = [];
if (!empty($minPrice)) $filters['min_price'] = $minPrice;
if (!empty($maxPrice)) $filters['max_price'] = $maxPrice;
if (!empty($bedrooms)) $filters['bedrooms'] = $bedrooms;
if (!empty($bathrooms)) $filters['bathrooms'] = $bathrooms;
if (!empty($propertyType)) $filters['property_type'] = $propertyType;
if (!empty($city)) $filters['city'] = $city;
if (!empty($search)) $filters['search'] = $search;

// Get filtered properties
$properties = empty($filters) ? [] : getProperties($filters);
?>

<!-- Hero Section with Search -->
<section class="hero">
    <h1>Find Your Dream Home</h1>
    <p>Search from thousands of properties across the country</p>

    <div class="search-bar">
        <form action="index.php" method="GET" class="search-form">
            <input type="text" name="search" placeholder="Search by location, property name or keyword..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>
</section>

<!-- Filter Bar -->
<div class="filter-bar">
    <h3>Filter Properties</h3>
    <form action="index.php" method="GET" class="filter-form">
        <div class="form-group">
            <label for="min_price" class="form-label">Min Price</label>
            <input type="number" id="min_price" name="min_price" class="form-control" placeholder="Min Price" value="<?php echo htmlspecialchars($minPrice); ?>">
        </div>

        <div class="form-group">
            <label for="max_price" class="form-label">Max Price</label>
            <input type="number" id="max_price" name="max_price" class="form-control" placeholder="Max Price" value="<?php echo htmlspecialchars($maxPrice); ?>">
        </div>

        <div class="form-group">
            <label for="bedrooms" class="form-label">Bedrooms</label>
            <select id="bedrooms" name="bedrooms" class="form-select">
                <option value="">Any</option>
                <option value="1" <?php if($bedrooms == '1') echo 'selected'; ?>>1+</option>
                <option value="2" <?php if($bedrooms == '2') echo 'selected'; ?>>2+</option>
                <option value="3" <?php if($bedrooms == '3') echo 'selected'; ?>>3+</option>
                <option value="4" <?php if($bedrooms == '4') echo 'selected'; ?>>4+</option>
                <option value="5" <?php if($bedrooms == '5') echo 'selected'; ?>>5+</option>
            </select>
        </div>

        <div class="form-group">
            <label for="bathrooms" class="form-label">Bathrooms</label>
            <select id="bathrooms" name="bathrooms" class="form-select">
                <option value="">Any</option>
                <option value="1" <?php if($bathrooms == '1') echo 'selected'; ?>>1+</option>
                <option value="2" <?php if($bathrooms == '2') echo 'selected'; ?>>2+</option>
                <option value="3" <?php if($bathrooms == '3') echo 'selected'; ?>>3+</option>
                <option value="4" <?php if($bathrooms == '4') echo 'selected'; ?>>4+</option>
            </select>
        </div>

        <div class="form-group">
            <label for="property_type" class="form-label">Property Type</label>
            <select id="property_type" name="property_type" class="form-select">
                <option value="">Any</option>
                <option value="house" <?php if($propertyType == 'house') echo 'selected'; ?>>House</option>
                <option value="apartment" <?php if($propertyType == 'apartment') echo 'selected'; ?>>Apartment</option>
                <option value="condo" <?php if($propertyType == 'condo') echo 'selected'; ?>>Condo</option>
                <option value="land" <?php if($propertyType == 'land') echo 'selected'; ?>>Land</option>
                <option value="commercial" <?php if($propertyType == 'commercial') echo 'selected'; ?>>Commercial</option>
            </select>
        </div>

        <div class="form-group">
            <label for="city" class="form-label">City</label>
            <input type="text" id="city" name="city" class="form-control" placeholder="City" value="<?php echo htmlspecialchars($city); ?>">
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
        </div>
    </form>
</div>

<?php if(!empty($filters)): ?>
    <!-- Search Results -->
    <section>
        <h2>Search Results</h2>

        <?php if(empty($properties)): ?>
            <div class="alert alert-info">No properties found matching your criteria. Please try a different search.</div>
        <?php else: ?>
            <div class="property-grid">
                <?php foreach($properties as $property): ?>
                    <div class="card">
                        <div class="card-image">
                            <img src="<?php echo htmlspecialchars(propertyImageUrl($property['image1'] ?? ''), ENT_QUOTES); ?>" alt="<?php echo $property['title']; ?>">
                        </div>
                        <div class="card-body">
                            <div class="card-price"><?php echo formatCurrency($property['price']); ?></div>
                            <h3 class="card-title"><?php echo $property['title']; ?></h3>
                            <p class="card-text"><?php echo substr($property['description'], 0, 100) . '...'; ?></p>
                            <div class="card-details">
                                <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Beds</span>
                                <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Baths</span>
                                <span><i class="fas fa-ruler-combined"></i> <?php echo $property['area']; ?> sqft</span>
                            </div>
                            <div class="card-type"><?php echo ucfirst($property['property_type']); ?></div>
                            <a href="property_details.php?id=<?php echo $property['id']; ?>" class="btn btn-primary btn-block">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php else: ?>
    <!-- Featured Properties -->
    <section>
        <h2 class="section-title">Featured Properties</h2>

        <?php if (empty($featuredProperties)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-house-circle-check"></i></div>
                <h3>New listings are on the way</h3>
                <p>There are no featured homes yet. Check back soon or create an account to start exploring the marketplace.</p>
                <a href="register.php" class="btn btn-primary">Create an account</a>
            </div>
        <?php else: ?>
            <div class="property-grid">
            <?php foreach($featuredProperties as $property): ?>
                <div class="card">
                    <div class="card-image">
                        <img src="<?php echo htmlspecialchars(propertyImageUrl($property['image1'] ?? ''), ENT_QUOTES); ?>" alt="<?php echo $property['title']; ?>">
                    </div>
                    <div class="card-body">
                        <div class="card-price"><?php echo formatCurrency($property['price']); ?></div>
                        <h3 class="card-title"><?php echo $property['title']; ?></h3>
                        <p class="card-text"><?php echo substr($property['description'], 0, 100) . '...'; ?></p>
                        <div class="card-details">
                            <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Beds</span>
                            <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Baths</span>
                            <span><i class="fas fa-ruler-combined"></i> <?php echo $property['area']; ?> sqft</span>
                        </div>
                        <div class="card-type"><?php echo ucfirst($property['property_type']); ?></div>
                        <a href="property_details.php?id=<?php echo $property['id']; ?>" class="btn btn-primary btn-block">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<!-- Interactive Map -->
<section>
    <h2 class="section-title">Find Properties on the Map</h2>
    <div id="map-container"></div>
</section>

    <!-- Property Categories -->
    <section class="browse-categories">
        <h2 class="section-title">Find Your Dream Home</h2>
        
        <div class="category-grid">
            <div class="category-card">
                <a href="index.php?property_type=house">
                    <div class="category-icon"><i class="fas fa-home"></i></div>
                    <h3>Residential Houses</h3>
                    <p>Find your perfect family home</p>
                    <?php 
                    $houseCount = count(getProperties(['property_type' => 'house']));
                    echo "<span class='property-count'>$houseCount listings</span>";
                    ?>
                </a>
            </div>
            
            <div class="category-card">
                <a href="index.php?property_type=apartment">
                    <div class="category-icon"><i class="fas fa-building"></i></div>
                    <h3>Apartments</h3>
                    <p>Modern urban living spaces</p>
                    <?php 
                    $apartmentCount = count(getProperties(['property_type' => 'apartment']));
                    echo "<span class='property-count'>$apartmentCount listings</span>";
                    ?>
                </a>
            </div>
            
            <div class="category-card">
                <a href="index.php?property_type=condo">
                    <div class="category-icon"><i class="fas fa-city"></i></div>
                    <h3>Condos</h3>
                    <p>Luxury condominium units</p>
                    <?php 
                    $condoCount = count(getProperties(['property_type' => 'condo']));
                    echo "<span class='property-count'>$condoCount listings</span>";
                    ?>
                </a>
            </div>
            
            <div class="category-card">
                <a href="index.php?property_type=land">
                    <div class="category-icon"><i class="fas fa-tree"></i></div>
                    <h3>Land</h3>
                    <p>Build your dream property</p>
                    <?php 
                    $landCount = count(getProperties(['property_type' => 'land']));
                    echo "<span class='property-count'>$landCount listings</span>";
                    ?>
                </a>
            </div>
            
            <div class="category-card">
                <a href="index.php?property_type=commercial">
                    <div class="category-icon"><i class="fas fa-store"></i></div>
                    <h3>Commercial</h3>
                    <p>Business spaces and offices</p>
                    <?php 
                    $commercialCount = count(getProperties(['property_type' => 'commercial']));
                    echo "<span class='property-count'>$commercialCount listings</span>";
                    ?>
                </a>
            </div>
        </div>

        <!-- Apartments -->
        <h3>Apartments</h3>
        <?php 
        $apartments = getProperties(['property_type' => 'apartment', 'limit' => 3]);
        if (empty($apartments)):
        ?>
            <div class="empty-state empty-state-compact">
                <div class="empty-state-icon"><i class="fas fa-building"></i></div>
                <h3>No apartments listed yet</h3>
                <p>Browse another category or return later for new apartment listings.</p>
            </div>
        <?php else: ?>
        <div class="property-grid">
            <?php foreach($apartments as $property): ?>
                <div class="card">
                    <div class="card-image">
                        <img src="<?php echo htmlspecialchars(propertyImageUrl($property['image1'] ?? ''), ENT_QUOTES); ?>" alt="<?php echo $property['title']; ?>">
                    </div>
                    <div class="card-body">
                        <div class="card-price"><?php echo formatCurrency($property['price']); ?></div>
                        <h3 class="card-title"><?php echo $property['title']; ?></h3>
                        <p class="card-text"><?php echo substr($property['description'], 0, 100) . '...'; ?></p>
                        <div class="card-details">
                            <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> Beds</span>
                            <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> Baths</span>
                            <span><i class="fas fa-ruler-combined"></i> <?php echo $property['area']; ?> sqft</span>
                        </div>
                        <a href="property_details.php?id=<?php echo $property['id']; ?>" class="btn btn-primary btn-block">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        </section>

<?php
// Additional scripts for map
$additionalScripts = '<script src="assets/js/map.js"></script>';

// Include footer
require_once 'includes/footer.php';
?>