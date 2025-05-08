<div class="manage_instructor">
    <div class="container">
        <h1>MANAGE Instrutor</h1>
        <div class="card">
            <h2>INSTRUCTOR LIST</h2>
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
                    <tr>
                        <td>Mikias Tadesse</td>
                        <td>male</td>
                        <td>BSCRIM</td>
                        <td>third year</td>
                        <td>codermiki@gmail.com</td>
                        <td>active</td>
                        <td><button class="update-btn">Update</button></td>
                    </tr>
                    <tr>
                        <td>Mikias Tadesse</td>
                        <td>male</td>
                        <td>BSCRIM</td>
                        <td>third year</td>
                        <td>codermiki@gmail.com</td>
                        <td>active</td>
                        <td><button class="update-btn">Update</button></td>
                    </tr>
                    <tr>
                        <td>Mikias Tadesse</td>
                        <td>male</td>
                        <td>BSCRIM</td>
                        <td>third year</td>
                        <td>codermiki@gmail.com</td>
                        <td>active</td>
                        <td><button class="update-btn">Update</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="updateModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Update (<span id="modalName">Name</span>)</h3>
        <form id="updateForm">
            <label>Fullname</label>
            <input type="text" id="fullname" />

            <label>Gender</label>
            <select id="gender">
                <option value="male">male</option>
                <option value="female">female</option>
            </select>

            <label>Course</label>
            <select id="course">
                <option value="BSIT">BSIT</option>
                <option value="BSHRM">BSHRM</option>
                <option value="BSCRIM">BSCRIM</option>
                <option value="WEB DESIGN">WEB DESIGN</option>
            </select>

            <label>Year level</label>
            <input type="text" id="yearLevel" />

            <label>Email</label>
            <input type="email" id="email" />

            <label>Status</label>
            <input type="text" id="status" />

            <button type="submit" class="update-btn">Update Now</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const modal = document.getElementById("updateModal");
        const closeBtn = document.querySelector(".close");
        const form = document.getElementById("updateForm");
        const updateButtons = document.querySelectorAll(".update-btn");

        const fields = [
            "fullname",
            "gender",
            "course",
            "yearLevel",
            "email",
            "status",
        ];

        updateButtons.forEach((btn) => {
            btn.addEventListener("click", () => {
                const row = btn.closest("tr").children;
                document.getElementById("modalName").textContent = row[0].textContent;

                fields.forEach((field, index) => {
                    document.getElementById(field).value = row[index].textContent.trim();
                });

                modal.style.display = "block";
            });
        });

        closeBtn.onclick = () => {
            modal.style.display = "none";
        };

        window.onclick = (e) => {
            if (e.target == modal) modal.style.display = "none";
        };

        form.onsubmit = (e) => {
            e.preventDefault();
            alert("Updated successfully (simulate backend here)");
            modal.style.display = "none";
        };
    });
</script>