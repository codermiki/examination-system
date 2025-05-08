<?php
$course = $_GET['course'] ?? null;
$year = $_GET['year'] ?? null;
$semester = $_GET['semester'] ?? null;

if (!$course || !$year || !$semester):
    ?>
    <div class="outer-wrapper">

        <div class="wrap">
            <!-- Filter form -->
            <h2>Select Filters to Manage Students</h2>
            <form method="GET" action="">
                <input type="hidden" name="page" value="manage_student">

                <label>Year</label>
                <select name="year" required>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                    <option value="5">5th Year</option>
                </select>

                <label>Semester</label>
                <select name="semester" required>
                    <option value="1">1st Semester</option>
                    <option value="2">2nd Semester</option>
                </select>

                <label>Course</label>
                <select name="course" required>
                    <option value="BSCRIM">BSCRIM</option>
                    <option value="WEB DESIGN">WEB DESIGN</option>
                    <option value="BSHRM">BSHRM</option>
                    <option value="BSIT">BSIT</option>
                </select>

                <button type="submit">View Students</button>
            </form>
        </div>
    </div>

<?php else: ?>

    <!-- STUDENT LIST TABLE with modal (your UI code goes here) -->
    <?php
    // TODO: Replace this with a real DB query
    $students = [
        [
            'fullname' => 'Mikias Tadesse',
            'gender' => 'male',
            'course' => $course,
            'year_level' => "$year year",
            'email' => 'codermiki@gmail.com',
            'status' => 'active'
        ],
        // add more from DB
    ];
    ?>

    <!-- PLACE YOUR UI HTML HERE (REPLACED STATIC TRs WITH DYNAMIC ONES) -->
    <div class="manage_instructor">
        <div class="container">
            <h1>MANAGE Student</h1>
            <div class="card">
                <h2>STUDENT LIST</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Fullname</th>
                            <th>Gender</th>
                            <th>Course</th>
                            <th>Year level</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $stu): ?>
                            <tr>
                                <td><?= htmlspecialchars($stu['fullname']) ?></td>
                                <td><?= htmlspecialchars($stu['gender']) ?></td>
                                <td><?= htmlspecialchars($stu['course']) ?></td>
                                <td><?= htmlspecialchars($stu['year_level']) ?></td>
                                <td><?= htmlspecialchars($stu['email']) ?></td>
                                <td><?= htmlspecialchars($stu['status']) ?></td>
                                <td>
                                    <button class="update-btn">Update</button>
                                    <button class="delete-btn">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal and JS stay the same -->
    <?php include 'student_modal.php'; ?>

<?php endif; ?>