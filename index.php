<?php
require_once __DIR__ . '/vendor/autoload.php';
use App\Models\Membership_plan;

$planModel = new Membership_plan();
$plans = $planModel->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitSync</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        /* ---------- Hero ---------- */
        .hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 32px;
            padding: 48px;
            border-radius: 20px;
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #60a5fa 100%);
            color: #fff;
            overflow: hidden;
        }
        .hero-text { flex: 1; min-width: 280px; }
        .hero-text h1 {
            font-size: 52px;
            line-height: 1.1;
            margin: 0 0 16px;
        }
        .hero-text h1 span { color: #93c5fd; }
        .hero-text p { font-size: 17px; max-width: 460px; margin-bottom: 28px; }
        .hero-buttons { display: flex; gap: 12px; flex-wrap: wrap; }

        .btn-light {
            background: #fff;
            color: #1d4ed8;
            padding: 12px 26px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
        }
        .btn-light:hover { background: #dbeafe; }

        .btn-outline {
            border: 2px solid #fff;
            color: #fff;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
        }
        .btn-outline:hover { background: rgba(255, 255, 255, 0.15); }

        .hero-image { flex: 1; min-width: 280px; }
        .hero-image img {
            width: 100%;
            height: 340px;
            object-fit: cover;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        }

        /* ---------- Feature cards ---------- */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }
        .feature {
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
        }
        .feature h3 { margin: 0 0 8px; color: #1d4ed8; }
        .feature p { margin: 0; font-size: 14px; color: #475569; }

        /* ---------- Why Train With Us ---------- */
        .why { margin-top: 32px; max-width: 900px; }
        .why h2 { font-size: 50px; margin-bottom: 14px; }
        .why h2 span { color: #2563eb; }

        .why-grid {
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .why-image { flex: 0 0 36%; }
        .why-image img {
            width: 250%;
            height: 500px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.25);
        }
        .why { margin-top: 30px; max-width: 60%; }   /* was 900px, now the section uses the full width */

        .why-image { flex: 0 0 30%; }                 /* image stays small */

        .why-list {
            flex: none;          /* stops it from stretching */
            width: 800px;            /* the size stays fixed */
            margin-left: 600px;      /* this moves it to the right */
            display: flex;
            flex-direction: column;
            gap: 8px;
            border-left: 4px solid #bfdbfe;
            padding-left: 50px;
}

        .why-item {
            background: #fff;
            border-radius: 10px;
            padding: 25px 50px;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;

            display: flex;
            align-items: center;
            gap: 16px;
        }
        .why-item h3 {
    margin: 30;
    font-size: 15px;
    flex: 10% 10% 10%;
}

.why-item p {
    margin: 0;
    font-size: 12px;
    color: #475569;
    flex: 1;
    text-align: right;
}
        .why-item:hover { background: #eff6ff; }
        .why-item.active {
            background: #2563eb;
            color: #fff;
        }
        .why-item.active p { color: #dbeafe; }

        /* ---------- Our Training Programs ---------- */
        .programs { margin-top: 32px; }
        .programs h2 { font-size: 24px; margin-bottom: 14px; }
        .programs h2 span { color: #2563eb; }

        .program-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }

        .program-card {
            background: #fff;
            border-radius: 14px;
            padding: 18px 20px;
            cursor: pointer;
            border: 2px solid transparent;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
            transition: transform 0.15s, border-color 0.2s;
        }
        .program-card:hover { transform: translateY(-4px); border-color: #2563eb; }
        .program-card h3 { margin: 0 0 6px; color: #1d4ed8; }
        .program-price { font-size: 22px; font-weight: 700; color: #2563eb; margin: 0; }
        .program-days { margin: 2px 0 8px; color: #64748b; font-size: 13px; }
        .program-desc { font-size: 13px; color: #475569; margin: 0 0 10px; }
        .program-link { color: #2563eb; font-weight: 600; font-size: 14px; }

        .program-img {
            width: 100%;
            height: 140px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 12px;
        }

        .btn-blue {
            display: inline-block;
            background: #2563eb;
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            margin-right: 8px;
        }
        .btn-blue:hover { background: #1d4ed8; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo">Fit<span>Sync</span></div>

        <div class="nav-links">
            <a href="login.php" class="nav-cta">Login</a>
            <a href="register.php" class="nav-cta">Register</a>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-text">
            <h1>Train Smart. <span>Stay Synced.</span></h1>
            <p>
                FitSync keeps your gym membership organized: browse plans,
                track your expiry date, and view your payments in one place.
            </p>
            <div class="hero-buttons">
                <a href="register.php" class="btn-light">Get Started</a>
                <a href="login.php" class="btn-outline">Login</a>
            </div>
        </div>

        <div class="hero-image">
            <img src="images/gym.png" alt="Gym">
        </div>
    </section>

    <section class="features">
        <div class="feature">
            <h3>Membership Plans</h3>
            <p>Browse available plans with their prices and durations.</p>
        </div>
        <div class="feature">
            <h3>Expiry Tracking</h3>
            <p>See when your membership ends so you never miss a renewal.</p>
        </div>
        <div class="feature">
            <h3>Payment Records</h3>
            <p>Keep a clear history of every payment you make.</p>
        </div>
    </section>

    <section class="why">
        <h2>Why Train With <span>Us?</span></h2>

        <div class="why-grid">
            <div class="why-image">
                <img src="images/gym.png" alt="Our gym">
            </div>

            <div class="why-list">
                <div class="why-item active">
                    <h3>Modern Equipment</h3>
                    <p>Train with well-maintained machines and weights built for every fitness level.</p>
                </div>
                <div class="why-item">
                    <h3>Flexible Memberships</h3>
                    <p>Pick a plan that fits your schedule and budget, from short to long term.</p>
                </div>
                <div class="why-item">
                    <h3>Never Miss a Renewal</h3>
                    <p>Track your expiry date online so your membership never lapses by surprise.</p>
                </div>
                <div class="why-item">
                    <h3>Clear Payment History</h3>
                    <p>Every payment is recorded, so you can always check what you've paid.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="programs">
        <h2>Our Training <span>Programs</span></h2>

        <?php if (empty($plans)): ?>
            <p>No programs available yet. Please check back soon.</p>
        <?php else: ?>
            <div class="program-grid">
                <?php foreach ($plans as $plan): ?>
                    <?php $img = ($plan["image"] ?? "") ?: "plan.png"; ?>
                    <div class="program-card" data-plan="<?= htmlspecialchars($plan["plan_name"]) ?>">
                        <img src="images/<?= htmlspecialchars($img) ?>" alt="" class="program-img">
                        <h3><?= htmlspecialchars($plan["plan_name"]) ?></h3>
                        <p class="program-price">₱<?= number_format($plan["plan_price"], 2) ?></p>
                        <p class="program-days"><?= (int) $plan["duration_days"] ?> days</p>
                        <p class="program-desc"><?= htmlspecialchars($plan["description"] ?? "") ?></p>
                        <span class="program-link">Join now →</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<footer class="footer">
    <div class="footer-col">
        <h3>Contact Us</h3>
        <p>FitSync</p>
        <p>Ground Floor, Universidad de Dagupan, Arrelano Str. Dagupan City, Pangasinan</p>
        <p>Open daily: 8:00 AM to 8:00 PM</p>
        <p>Phone: 0967 676 7676</p>
        <p>Email: JericoBabies@gmail.com</p>
    </div>

    <div class="footer-col">
        <h3>Terms and Conditions</h3>
        <p>Memberships are personal and cannot be transferred to another person.</p>
        <p>Membership fees are non-refundable once the membership has started.</p>
        <p>Access is allowed only while your membership is active and not expired.</p>
        <p>Members must follow gym rules and use equipment responsibly.</p>
    </div>

    <p class="footer-copy">&copy; 2026 FitSync. All rights reserved.</p>
</footer>
    <dialog id="joinDialog">
        <h2>Join <span id="joinPlanName"></span></h2>
        <p>Log in or create an account to subscribe to this plan.</p>
        <a href="login.php" class="btn-blue">Login</a>
        <a href="register.php" class="btn-blue">Sign Up</a>
        <button type="button" id="closeJoin">Cancel</button>
    </dialog>

    <script>
        var items = document.querySelectorAll(".why-item");
        items.forEach(function (item) {
            item.onclick = function () {
                items.forEach(function (i) { i.classList.remove("active"); });
                item.classList.add("active");
            };
        });

        var joinDialog = document.getElementById("joinDialog");
        document.querySelectorAll(".program-card").forEach(function (card) {
            card.onclick = function () {
                document.getElementById("joinPlanName").textContent = card.dataset.plan;
                joinDialog.showModal();
            };
        });
        document.getElementById("closeJoin").onclick = function () { joinDialog.close(); };
    </script>

</body>
</html>