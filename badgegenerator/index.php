<?php
require_once 'logic.php';

$all_siswa = getAllSiswa();
$selected_id = $_GET['id'] ?? ($all_siswa[0]['id'] ?? null);
$siswa = $selected_id ? getSiswaById($selected_id) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $siswa ? htmlspecialchars($siswa['nama']) : 'Sistem Biodata Siswa' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background-color: #000000;
            color: #e2e2e2;
            font-family: 'Inter', sans-serif;
            display: flex;
            justify-content: center;
            padding: 40px 20px;
            min-height: 100vh;
        }

        .main-layout {
            width: 100%;
            max-width: 1000px;
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 24px;
        }

        @media (max-width: 800px) {
            .main-layout { grid-template-columns: 1fr; }
        }

        .card {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 24px;
            backdrop-filter: blur(10px);
        }

        /* Sidebar Styling */
        .sidebar { display: flex; flex-direction: column; gap: 16px; }
        .sidebar-header { display: flex; justify-content: space-between; align-items: center; }
        .btn-add {
            background: #39ff14;
            color: #000;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .siswa-list { display: flex; flex-direction: column; gap: 8px; max-height: 70vh; overflow-y: auto; }
        .siswa-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            text-decoration: none;
            color: inherit;
            transition: all 0.2s;
        }
        .siswa-item:hover, .siswa-item.active {
            background: rgba(57, 255, 20, 0.1);
            border-color: #39ff14;
        }
        .avatar-mini {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #222;
            object-fit: cover;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Detail Section Styling */
        .container { display: flex; flex-direction: column; gap: 24px; }
        .hero-card { text-align: center; display: flex; flex-direction: column; align-items: center; position: relative; }
        .avatar-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            overflow: hidden;
        }
        .avatar-img { width: 100%; height: 100%; object-fit: cover; }
        .profile-name { font-size: 28px; font-weight: 700; margin-bottom: 8px; }
        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #baccb0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .green-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #39ff14;
            box-shadow: 0 0 8px #39ff14;
        }
        .grid-two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media (max-width: 500px) { .grid-two-columns { grid-template-columns: 1fr; } }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #888888;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }
        .card-header .material-icons { font-size: 16px; }
        .card-value { font-size: 18px; font-weight: 600; }
        .list-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .list-item:last-child { border-bottom: none; }
        .list-label { color: #888888; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .card-highlight { border-color: #39ff14; box-shadow: 0 0 15px rgba(57, 255, 20, 0.15); display: flex; justify-content: space-between; align-items: center; }
        .highlight-text { color: #39ff14; }
        .large-status { font-size: 36px; font-weight: 700; margin-top: 4px; text-transform: capitalize; }

        /* Form Modal */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); justify-content: center; align-items: center; z-index: 100; padding: 20px; }
        .modal:target { display: flex; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px; }
        input, textarea, select {
            width: 100%; padding: 10px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2);
            border-radius: 6px; color: #fff; font-family: inherit; margin-top: 4px;
        }
        .btn-submit { grid-column: span 2; background: #39ff14; color: #000; font-weight: 700; padding: 12px; border: none; border-radius: 6px; cursor: pointer; }

        .btn-nav-glass {
            background: rgba(255, 255, 255, 0.05);
            color: #e2e2e2;
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 8px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-nav-glass:hover {
            background: rgba(57, 255, 20, 0.1);
            border-color: #39ff14;
            color: #39ff14;
            box-shadow: 0 0 10px rgba(57, 255, 20, 0.2);
        }
        /* Glassmorphism Error Banner & Pop-up */
        .alert-error {
            background: rgba(255, 68, 68, 0.15);
            border: 1px solid #ff4444;
            color: #ff6b6b;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>

    <div class="main-layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Directory</h3>

                <div style="display: flex; gap: 8px; align-items: center;">
                    <!-- New PassACRUD Link -->
                    <a href="http://localhost/passcrud" class="btn-nav-glass">
                        <span class="material-icons" style="font-size: 16px;">key</span> PassCRUD
                    </a>
                    
                    <!-- Existing Add Button -->
                    <a href="#add-modal" class="btn-add"><span class="material-icons">add</span> Add</a>
                </div>
            </div>
            <div class="siswa-list">
                <?php if (empty($all_siswa)): ?>
                    <p style="color: #666; font-size: 14px;">No students found.</p>
                <?php else: ?>
                    <?php foreach ($all_siswa as $item): ?>
                        <a href="?id=<?= $item['id'] ?>" class="siswa-item <?= ($siswa && $siswa['id'] == $item['id']) ? 'active' : '' ?>">
                            <?php if (!empty($item['foto'])): ?>
                                <img src="data:image/jpeg;base64,<?= base64_encode($item['foto']) ?>" class="avatar-mini" alt="">
                            <?php else: ?>
                                <div class="avatar-mini"><span class="material-icons">person</span></div>
                            <?php endif; ?>
                            <div>
                                <div style="font-weight: 600; font-size: 14px;"><?= htmlspecialchars($item['nama']) ?></div>
                                <div style="font-size: 12px; color: #888;"><?= htmlspecialchars($item['kelas']) ?> - <?= htmlspecialchars($item['jurusan']) ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </aside>

        <!-- Main Detail Display -->
        <main class="container">
            <?php if ($siswa): ?>
                <section class="card hero-card">
                    <!-- Insert this inside <div id="add-modal"> before <form> -->
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert-error">
                            <span class="material-icons">error_outline</span>
                            <span><?= htmlspecialchars($_GET['error']) ?></span>
                        </div>
                    <?php endif; ?>
                    <form action="logic.php" method="POST" onsubmit="return confirm('Delete this student?');" style="position: absolute; right: 20px; top: 20px;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $siswa['id'] ?>">
                        <button style="background:none; border:none; color:#ff4444; cursor:pointer;"><span class="material-icons">delete</span></button>
                    </form>

                    <div class="avatar-circle">
                        <?php if (!empty($siswa['foto'])): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($siswa['foto']) ?>" class="avatar-img" alt="<?= htmlspecialchars($siswa['nama']) ?>">
                        <?php else: ?>
                            <span class="material-icons avatar-icon">person</span>
                        <?php endif; ?>
                    </div>
                    <h1 class="profile-name"><?= htmlspecialchars($siswa['nama']) ?></h1>
                    <div class="badge-status">
                        <span class="green-dot"></span>
                        <span>NIS: <?= htmlspecialchars($siswa['nis']) ?> / NISN: <?= htmlspecialchars($siswa['nisN']) ?></span>
                    </div>
                </section>

                <section class="grid-two-columns">
                    <div class="card">
                        <div class="card-header">
                            <span class="material-icons">location_on</span>
                            <span>Address</span>
                        </div>
                        <p class="card-value"><?= htmlspecialchars($siswa['alamat']) ?></p>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <span class="material-icons">phone</span>
                            <span>Contact Number</span>
                        </div>
                        <p class="card-value"><?= htmlspecialchars($siswa['nomor_hp']) ?></p>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header highlight-text">
                        <span class="material-icons">school</span>
                        <span>Academic Record</span>
                    </div>

                    <div class="list-item">
                        <span class="list-label">Class</span>
                        <span class="card-value"><?= htmlspecialchars($siswa['kelas']) ?></span>
                    </div>

                    <div class="list-item">
                        <span class="list-label">Specialization</span>
                        <span class="card-value"><?= htmlspecialchars($siswa['jurusan']) ?></span>
                    </div>

                    <div class="list-item">
                        <span class="list-label">Rombel</span>
                        <span class="card-value"><?= htmlspecialchars($siswa['rombel']) ?></span>
                    </div>
                </section>

                <section class="card card-highlight">
                    <div>
                        <div class="card-header highlight-text">
                            <span class="material-icons">flag</span>
                            <span>Aspiration / Cita-Cita</span>
                        </div>
                        <p class="large-status"><?= htmlspecialchars($siswa['cita_cita']) ?></p>
                    </div>
                    <div class="green-dot" style="width: 12px; height: 12px;"></div>
                </section>
            <?php else: ?>
                <div class="card" style="text-align: center; padding: 60px;">
                    <h2>No Student Selected</h2>
                    <p style="color: #888; margin-top: 8px;">Click "Add" in the sidebar to create a new profile.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal Form for Creating Student Entry -->
    <div id="add-modal" class="modal">
        <div class="card" style="width: 100%; max-width: 500px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h3>Add New Student</h3>
                <a href="#" style="color: #fff; text-decoration: none;"><span class="material-icons">close</span></a>
            </div>
            
            <form action="logic.php" method="POST" enctype="multipart/form-data" class="form-grid">
                <input type="hidden" name="action" value="create">
                
                <div>
                    <label class="list-label">NIS</label>
                    <input type="text" name="nis" required>
                </div>
                <div>
                    <label class="list-label">NISN</label>
                    <input type="text" name="nisN" required>
                </div>
                <div style="grid-column: span 2;">
                    <label class="list-label">Full Name</label>
                    <input type="text" name="nama" required>
                </div>
                <div>
                    <label class="list-label">Class</label>
                    <input type="text" name="kelas" placeholder="e.g. 11" required>
                </div>
                <div>
                    <label class="list-label">Major/Jurusan</label>
                    <input type="text" name="jurusan" placeholder="e.g. RPL" required>
                </div>
                <div>
                    <label class="list-label">Rombel</label>
                    <input type="text" name="rombel" placeholder="e.g. A" required>
                </div>
                <div>
                    <label class="list-label">Phone Number</label>
                    <input type="text" name="nomor_hp" required>
                </div>
                <div style="grid-column: span 2;">
                    <label class="list-label">Address</label>
                    <textarea name="alamat" rows="2" required></textarea>
                </div>
                <div style="grid-column: span 2;">
                    <label class="list-label">Cita-Cita / Goal</label>
                    <input type="text" name="cita_cita">
                </div>
                <div style="grid-column: span 2;">
                    <label class="list-label">Photo Profile</label>
                    <input type="file" name="foto" accept="image/*">
                </div>
                
                <button type="submit" class="btn-submit">Save Student Record</button>
            </form>
        </div>
    </div>
    <?php if (isset($_GET['error'])): ?>
    <script>
        // Trigger window alert pop-up immediately if duplicate detected
        window.addEventListener('DOMContentLoaded', () => {
            alert("⚠️ Duplicate Error:\n<?= htmlspecialchars($_GET['error']) ?>");
        });
    </script>
    <?php endif; ?>
</body>
</html>