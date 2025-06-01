    <?php
    require '../config.php';

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header('Location: ../login.php');
        exit;
    }
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Create Quiz - Admin - Quizify</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            :root {
                --primary: #6366f1;
                --primary-dark: #4f46e5;
                --secondary: #ec4899;
                --accent: #06b6d4;
                --success: #10b981;
                --warning: #f59e0b;
                --danger: #ef4444;
                --dark: #0f172a;
                --dark-light: #1e293b;
                --gray: #64748b;
                --gray-light: #94a3b8;
                --gray-lighter: #cbd5e1;
                --white: #ffffff;
                --bg-primary: #f8fafc;
                --bg-secondary: #f1f5f9;
                --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                --radius: 12px;
                --radius-lg: 16px;
                --radius-xl: 20px;
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Poppins', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                color: var(--dark);
                overflow-x: hidden;
                display: flex;
                flex-direction: column;
            }

            /* Animated Background */
            body::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: 
                    radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                    radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                    radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.3) 0%, transparent 50%);
                z-index: -1;
                animation: backgroundShift 20s ease-in-out infinite;
            }

            @keyframes backgroundShift {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.8; }
            }

            /* Top Navbar */
            .top-navbar {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 70px;
                background: rgba(15, 23, 42, 0.95);
                backdrop-filter: blur(20px);
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                z-index: 1001;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0 2rem;
                transition: all 0.3s ease;
            }

            .navbar-brand {
                display: flex;
                align-items: center;
                gap: 1rem;
                color: white;
                text-decoration: none;
            }

            .navbar-logo {
                width: 40px;
                height: 40px;
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                border-radius: var(--radius);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.2rem;
                color: white;
                animation: logoFloat 6s ease-in-out infinite;
            }

            .navbar-title {
                font-size: 1.5rem;
                font-weight: 700;
                background: linear-gradient(135deg, #fff, #e2e8f0);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }

            .navbar-actions {
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .navbar-time {
                color: var(--gray-light);
                font-size: 0.875rem;
                font-weight: 500;
            }

            .navbar-user {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.5rem 1rem;
                background: rgba(255, 255, 255, 0.1);
                border-radius: var(--radius);
                color: white;
                transition: all 0.3s ease;
            }

            .navbar-user:hover {
                background: rgba(255, 255, 255, 0.15);
                transform: translateY(-1px);
            }

            .navbar-avatar {
                width: 32px;
                height: 32px;
                background: linear-gradient(135deg, var(--accent), var(--success));
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: 600;
                font-size: 0.875rem;
            }

            .navbar-username {
                font-weight: 600;
                font-size: 0.875rem;
            }

            /* Sidebar */
            .sidebar {
                position: fixed;
                left: 0;
                top: 70px;
                width: 280px;
                height: calc(100vh - 70px);
                background: rgba(15, 23, 42, 0.95);
                backdrop-filter: blur(20px);
                border-right: 1px solid rgba(255, 255, 255, 0.1);
                z-index: 1000;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                overflow-y: auto;
            }

            .sidebar-content {
                padding: 1.5rem 0;
            }

            .nav-menu {
                padding: 1rem 0;
                list-style: none;
            }

            .nav-item {
                margin: 0.25rem 0;
            }

            .nav-link {
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 1rem 1.5rem;
                color: var(--gray-light);
                text-decoration: none;
                font-weight: 500;
                transition: all 0.3s ease;
                border-radius: 0 var(--radius-xl) var(--radius-xl) 0;
                margin-right: 1rem;
                position: relative;
                overflow: hidden;
            }

            .nav-link::before {
                content: '';
                position: absolute;
                left: 0;
                top: 0;
                width: 4px;
                height: 100%;
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                transform: scaleY(0);
                transition: transform 0.3s ease;
            }

            .nav-link:hover,
            .nav-link.active {
                background: rgba(99, 102, 241, 0.1);
                color: white;
                transform: translateX(8px);
            }

            .nav-link:hover::before,
            .nav-link.active::before {
                transform: scaleY(1);
            }

            .nav-link i {
                width: 20px;
                text-align: center;
                font-size: 1.1rem;
            }

            /* Main Content */
            .main-content {
                margin-left: 280px;
                margin-top: 70px;
                padding: 2rem;
                min-height: calc(100vh - 70px);
                transition: all 0.4s ease;
                flex: 1;
            }

            .page-header {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(20px);
                border-radius: var(--radius-xl);
                padding: 2rem;
                margin-bottom: 2rem;
                box-shadow: var(--shadow-lg);
                border: 1px solid rgba(255, 255, 255, 0.2);
                position: relative;
                overflow: hidden;
                text-align: center;
            }

            .page-header::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));
                background-size: 200% 100%;
                animation: gradientShift 3s ease-in-out infinite;
            }

            @keyframes gradientShift {
                0%, 100% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
            }

            .page-title {
                font-size: 2.5rem;
                font-weight: 800;
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                margin: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 1rem;
            }

            .page-title i {
                font-size: 2rem;
            }

            /* Enhanced Quiz Form */
            .quiz-form {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(20px);
                padding: 3rem;
                border-radius: var(--radius-xl);
                box-shadow: var(--shadow-xl);
                max-width: 900px;
                margin: 0 auto;
                width: 100%;
                position: relative;
                border: 1px solid rgba(255, 255, 255, 0.2);
                animation: fadeInScale 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .quiz-form::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));
                border-radius: var(--radius-xl) var(--radius-xl) 0 0;
                background-size: 200% 100%;
                animation: gradientShift 3s ease-in-out infinite;
            }

            .form-group {
                margin-bottom: 2.5rem;
                position: relative;
            }

            .form-group label {
                display: block;
                font-weight: 600;
                margin-bottom: 0.75rem;
                color: var(--dark);
                font-size: 1.1rem;
                position: relative;
                padding-left: 1.5rem;
            }

            .form-group label::before {
                content: "";
                position: absolute;
                left: 0;
                top: 50%;
                transform: translateY(-50%);
                width: 4px;
                height: 20px;
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                border-radius: 2px;
            }

            input[type="text"],
            input[type="number"] {
                width: 100%;
                padding: 1rem 1.25rem;
                border: 2px solid var(--gray-lighter);
                border-radius: var(--radius);
                font-size: 1rem;
                color: var(--dark);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                background: rgba(255, 255, 255, 0.8);
                box-shadow: var(--shadow-sm);
                font-family: 'Poppins', sans-serif;
            }

            input[type="text"]:focus,
            input[type="number"]:focus {
                outline: none;
                border-color: var(--primary);
                box-shadow: 
                    0 0 0 4px rgba(99, 102, 241, 0.1),
                    var(--shadow);
                transform: translateY(-1px);
                background: white;
            }

            /* Enhanced Question Blocks */
            .question-block {
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(20px);
                border-radius: var(--radius-lg);
                padding: 0;
                margin-bottom: 2.5rem;
                box-shadow: var(--shadow-lg);
                border: 1px solid rgba(255, 255, 255, 0.2);
                position: relative;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                overflow: hidden;
                animation: slideInUp 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .question-block:hover {
                box-shadow: var(--shadow-xl);
                transform: translateY(-4px);
            }

            .question-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                padding: 1.5rem 2rem;
                background: linear-gradient(135deg, var(--dark) 0%, var(--dark-light) 100%);
                color: white;
                position: relative;
            }

            .question-header::after {
                content: "";
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                height: 1px;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            }

            .question-header label {
                font-size: 1.25rem;
                color: white;
                margin-bottom: 0;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                font-weight: 600;
                padding-left: 0;
            }

            .question-header label::before {
                display: none;
            }

            .question-header label i {
                color: #a5b4fc;
                font-size: 1.1rem;
            }

            .question-type-select {
                padding: 0.75rem 1rem;
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: var(--radius);
                font-size: 0.9rem;
                background: #ffff;
                color: black;
                min-width: 180px;
                cursor: pointer;
                transition: all 0.3s ease;
                font-family: 'Poppins', sans-serif;
            }

            .question-type-select:focus {
                outline: none;
                border-color: rgba(255, 255, 255, 0.5);
                background: rgba(255, 255, 255, 0.15);
            }

            .delete-question-btn {
                background: rgba(239, 68, 68, 0.2);
                color: #fca5a5;
                border: none;
                border-radius: var(--radius);
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.3s ease;
                backdrop-filter: blur(10px);
            }

            .delete-question-btn:hover {
                background: var(--danger);
                color: white;
                transform: scale(1.1);
                box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
            }

            /* Question Content Area */
            .question-content {
                padding: 2rem;
            }

            /* Enhanced Toolbar */
            .toolbar {
                display: flex;
                gap: 0.5rem;
                margin-bottom: 1.5rem;
                padding: 1rem;
                background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
                border-radius: var(--radius);
                border: 1px solid var(--gray-lighter);
                flex-wrap: wrap;
                box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06);
            }

            .toolbar button {
                padding: 0.75rem;
                border: none;
                border-radius: var(--radius);
                background: white;
                color: var(--gray);
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                font-size: 0.9rem;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                box-shadow: var(--shadow-sm);
            }

            .toolbar button:hover {
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                color: white;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
            }

            /* Enhanced Content Editable */
            .contenteditable {
                min-height: 120px;
                padding: 1.25rem;
                border: 2px solid var(--gray-lighter);
                border-radius: var(--radius);
                margin-bottom: 1.5rem;
                background: rgba(255, 255, 255, 0.8);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                font-size: 1rem;
                line-height: 1.6;
                box-shadow: var(--shadow-sm);
                font-family: 'Poppins', sans-serif;
            }

            .contenteditable:focus {
                outline: none;
                border-color: var(--primary);
                box-shadow: 
                    0 0 0 4px rgba(99, 102, 241, 0.1),
                    var(--shadow);
                background: white;
                transform: translateY(-1px);
            }

            .contenteditable[data-placeholder]:empty:before {
                content: attr(data-placeholder);
                color: var(--gray-light);
                font-style: italic;
            }

            /* Enhanced Choices */
            .choices {
                margin: 2rem 0;
            }

            .choice-item {
                display: flex;
                align-items: flex-start;
                gap: 1.25rem;
                margin-bottom: 1.5rem;
                padding: 1.5rem;
                background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
                border-radius: var(--radius);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                border: 2px solid transparent;
                position: relative;
                overflow: hidden;
                animation: fadeInScale 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .choice-item::before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                width: 4px;
                height: 100%;
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            .choice-item:hover {
                background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
                border-color: #c7d2fe;
                transform: translateX(4px);
            }

            .choice-item:hover::before {
                opacity: 1;
            }

            .choice-radio {
                margin-top: 1rem;
                width: 1.5rem;
                height: 1.5rem;
                accent-color: var(--primary);
                cursor: pointer;
                transition: transform 0.2s ease;
            }

            .choice-radio:hover {
                transform: scale(1.1);
            }

            .choice-content {
                min-height: 80px;
                flex-grow: 1;
                background: rgba(255, 255, 255, 0.9);
                padding: 1.25rem;
                border-radius: var(--radius);
                border: 2px solid var(--gray-lighter);
                transition: all 0.3s ease;
                box-shadow: var(--shadow-sm);
            }

            .choice-content:focus {
                outline: none;
                border-color: var(--primary);
                box-shadow: 
                    0 0 0 4px rgba(99, 102, 241, 0.1),
                    var(--shadow);
                background: white;
            }

            .remove-choice-btn {
                background: linear-gradient(135deg, #fee2e2, #fecaca);
                color: var(--danger);
                border: none;
                border-radius: var(--radius);
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                margin-top: 0.5rem;
                box-shadow: var(--shadow-sm);
            }

            .remove-choice-btn:hover {
                background: linear-gradient(135deg, var(--danger), #b91c1c);
                color: white;
                transform: scale(1.1);
                box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
            }

            /* Enhanced Buttons */
            .add-choice-btn {
                background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
                color: var(--primary);
                border: 2px dashed #a5b4fc;
                border-radius: var(--radius);
                padding: 1rem 2rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                display: inline-flex;
                align-items: center;
                gap: 0.75rem;
                margin-bottom: 2rem;
                width: 100%;
                justify-content: center;
                font-size: 1rem;
                font-family: 'Poppins', sans-serif;
            }

            .add-choice-btn:hover {
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                color: white;
                border-color: var(--primary);
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
            }

            .add-question-btn {
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                color: white;
                border: none;
                border-radius: var(--radius);
                padding: 1.25rem 2.5rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                display: inline-flex;
                align-items: center;
                gap: 0.75rem;
                margin: 2rem 0;
                font-size: 1.1rem;
                box-shadow: 0 4px 14px rgba(99, 102, 241, 0.3);
                font-family: 'Poppins', sans-serif;
            }

            .add-question-btn:hover {
                background: linear-gradient(135deg, var(--primary-dark), #6d28d9);
                transform: translateY(-3px);
                box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
            }

            .submit-btn {
                background: linear-gradient(135deg, var(--success), #047857);
                color: white;
                border: none;
                border-radius: var(--radius);
                padding: 1.25rem 2.5rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                display: inline-flex;
                align-items: center;
                gap: 0.75rem;
                font-size: 1.1rem;
                box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
                font-family: 'Poppins', sans-serif;
            }

            .submit-btn:hover {
                background: linear-gradient(135deg, #047857, #065f46);
                transform: translateY(-3px);
                box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
            }

            /* Form Actions */
            .form-actions {
                display: flex;
                gap: 1.5rem;
                margin-top: 3rem;
                justify-content: center;
                flex-wrap: wrap;
            }

            .form-actions button {
                min-width: 200px;
            }

            /* Helper Text */
            .helper-text {
                color: var(--gray);
                font-size: 0.875rem;
                margin-top: 0.75rem;
                display: block;
                font-style: italic;
                padding-left: 1rem;
                border-left: 3px solid var(--gray-lighter);
            }

            /* Footer */
            .footer {
                background: rgba(15, 23, 42, 0.95);
                backdrop-filter: blur(20px);
                border-top: 1px solid rgba(255, 255, 255, 0.1);
                color: white;
                padding: 3rem 2rem 2rem;
                margin-left: 280px;
                margin-top: auto;
            }

            .footer-content {
                max-width: 1200px;
                margin: 0 auto;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 2rem;
            }

            .footer-section h3 {
                font-size: 1.25rem;
                font-weight: 700;
                margin-bottom: 1rem;
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }

            .footer-section p,
            .footer-section li {
                color: var(--gray-light);
                line-height: 1.6;
                margin-bottom: 0.5rem;
            }

            .footer-section ul {
                list-style: none;
            }

            .footer-section a {
                color: var(--gray-light);
                text-decoration: none;
                transition: color 0.3s ease;
            }

            .footer-section a:hover {
                color: white;
            }

            .footer-social {
                display: flex;
                gap: 1rem;
                margin-top: 1rem;
            }

            .social-link {
                width: 40px;
                height: 40px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                text-decoration: none;
                transition: all 0.3s ease;
            }

            .social-link:hover {
                background: var(--primary);
                transform: translateY(-2px);
            }

            .footer-bottom {
                border-top: 1px solid rgba(255, 255, 255, 0.1);
                margin-top: 2rem;
                padding-top: 2rem;
                text-align: center;
                color: var(--gray-light);
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 1rem;
            }

            .footer-logo {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                font-weight: 700;
                color: white;
            }

            .footer-logo i {
                font-size: 1.5rem;
                background: linear-gradient(135deg, var(--primary), var(--secondary));
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }

            /* Mobile Menu */
            .mobile-menu-btn {
                display: none;
                position: fixed;
                top: 85px;
                left: 1rem;
                z-index: 1001;
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(10px);
                border: none;
                border-radius: var(--radius);
                width: 48px;
                height: 48px;
                color: var(--primary);
                font-size: 1.2rem;
                cursor: pointer;
                box-shadow: var(--shadow);
                transition: all 0.3s ease;
            }

            .mobile-menu-btn:hover {
                background: white;
                transform: scale(1.05);
            }

            /* Responsive Design */
            @media (max-width: 1024px) {
                .sidebar {
                    transform: translateX(-100%);
                }

                .sidebar.active {
                    transform: translateX(0);
                }

                .main-content,
                .footer {
                    margin-left: 0;
                }

                .mobile-menu-btn {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
            }

            @media (max-width: 768px) {
                .main-content {
                    padding: 1rem;
                }

                .quiz-form {
                    padding: 1.5rem;
                    border-radius: var(--radius-lg);
                }

                .page-title {
                    font-size: 1.75rem;
                    flex-direction: column;
                    gap: 0.5rem;
                }

                .question-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 1rem;
                    padding: 1.25rem;
                }

                .question-type-select {
                    width: 100%;
                }

                .delete-question-btn {
                    position: absolute;
                    top: 1rem;
                    right: 1rem;
                }

                .form-actions {
                    flex-direction: column;
                }

                .form-actions button {
                    width: 100%;
                    min-width: auto;
                }

                .choice-item {
                    flex-direction: column;
                    gap: 1rem;
                }

                .choice-radio {
                    margin-top: 0;
                    align-self: flex-start;
                }

                .toolbar {
                    justify-content: center;
                }

                .footer {
                    padding: 2rem 1rem 1.5rem;
                }

                .footer-content {
                    grid-template-columns: 1fr;
                    gap: 1.5rem;
                }

                .footer-bottom {
                    flex-direction: column;
                    text-align: center;
                }
            }

            /* Animation Enhancements */
            @keyframes slideInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes fadeInScale {
                from {
                    opacity: 0;
                    transform: scale(0.95);
                }
                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }

            @keyframes logoFloat {
                0%, 100% { transform: translateY(0px) rotate(0deg); }
                50% { transform: translateY(-5px) rotate(2deg); }
            }

            /* Scrollbar Styling */
            .sidebar::-webkit-scrollbar {
                width: 6px;
            }

            .sidebar::-webkit-scrollbar-track {
                background: transparent;
            }

            .sidebar::-webkit-scrollbar-thumb {
                background: rgba(255, 255, 255, 0.2);
                border-radius: 3px;
            }

            .sidebar::-webkit-scrollbar-thumb:hover {
                background: rgba(255, 255, 255, 0.3);
            }
        </style>
        <script>
            function editorCommand(command, arg = null) {
                document.execCommand(command, false, arg);
            }

            function insertImageAtCaret(src) {
                const selection = window.getSelection();
                if (!selection.rangeCount) return false;
                const range = selection.getRangeAt(0);

                const img = document.createElement('img');
                img.src = src;
                img.style.maxWidth = '100%';
                img.style.height = 'auto';
                img.draggable = false;

                const wrapper = document.createElement('span');
                wrapper.className = 'resizable-image';
                wrapper.contentEditable = 'false';

                const handle = document.createElement('span');
                handle.className = 'resize-handle';

                wrapper.appendChild(img);
                wrapper.appendChild(handle);

                range.deleteContents();
                range.insertNode(wrapper);
                range.setStartAfter(wrapper);
                range.collapse(true);
                selection.removeAllRanges();
                selection.addRange(range);

                if (isInsideEditableArea(wrapper)) {
                    setupImageInteractions(wrapper, img, handle);
                }
            }

            function isInsideEditableArea(element) {
                if (!element) return false;
                let parent = element.parentElement;
                while (parent) {
                    if (parent.classList.contains('contenteditable') || parent.classList.contains('choice-content')) {
                        return true;
                    }
                    parent = parent.parentElement;
                }
                return false;
            }

            function uploadImage(inputId) {
                document.getElementById(inputId).click();
            }

            function handleImageUpload(event, editorId) {
                const input = event.target;
                if (input.files && input.files[0]) {
                    const file = input.files[0];
                    if (!file.type.startsWith('image/')) {
                        alert('Please select an image file.');
                        return;
                    }
                    const formData = new FormData();
                    formData.append('image', file);

                    fetch('upload_image.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success && data.url) {
                                const editor = document.getElementById(editorId);
                                editor.focus();
                                placeCaretAtEnd(editor);
                                insertImageAtCaret(data.url);
                            } else {
                                alert('Image upload failed: ' + (data.error || 'Unknown error'));
                            }
                        })
                        .catch(() => alert('Failed to upload image.'))
                        .finally(() => { input.value = ''; });
                }
            }

            function placeCaretAtEnd(el) {
                el.focus();
                if (typeof window.getSelection != "undefined"
                    && typeof document.createRange != "undefined") {
                    const range = document.createRange();
                    range.selectNodeContents(el);
                    range.collapse(false);
                    const sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(range);
                }
            }

            let selectedWrapper = null;
            let resizeData = null;
            let dragData = null;

            function setupImageInteractions(wrapper, img, handle) {
                wrapper.addEventListener('click', e => {
                    e.stopPropagation();
                    selectWrapper(wrapper);
                });

                document.addEventListener('click', e => {
                    if (selectedWrapper && selectedWrapper !== wrapper) {
                        deselectWrapper(selectedWrapper);
                    }
                });

                handle.addEventListener('mousedown', e => {
                    e.preventDefault();
                    e.stopPropagation();
                    resizeData = {
                        startX: e.clientX,
                        startY: e.clientY,
                        startWidth: img.offsetWidth,
                        startHeight: img.offsetHeight,
                        img: img
                    };
                    window.addEventListener('mousemove', onResize);
                    window.addEventListener('mouseup', stopResize);
                });

                wrapper.style.position = 'relative';
                wrapper.style.display = 'inline-block';

                wrapper.addEventListener('mousedown', e => {
                    if (e.target === handle) return;

                    e.preventDefault();
                    e.stopPropagation();
                    dragData = {
                        startX: e.clientX,
                        startY: e.clientY,
                        origLeft: parseInt(wrapper.style.left || 0),
                        origTop: parseInt(wrapper.style.top || 0),
                        wrapper: wrapper
                    };
                    window.addEventListener('mousemove', onDrag);
                    window.addEventListener('mouseup', stopDrag);
                    selectWrapper(wrapper);
                });
            }

            function selectWrapper(wrapper) {
                if (selectedWrapper && selectedWrapper !== wrapper) {
                    deselectWrapper(selectedWrapper);
                }
                selectedWrapper = wrapper;
                wrapper.classList.add('selected');
            }

            function deselectWrapper(wrapper) {
                wrapper.classList.remove('selected');
                if (selectedWrapper === wrapper) selectedWrapper = null;
            }

            function onResize(e) {
                if (!resizeData) return;
                let dx = e.clientX - resizeData.startX;
                let dy = e.clientY - resizeData.startY;

                let newWidth = resizeData.startWidth + dx;
                let newHeight = resizeData.startHeight + dy;
                const aspectRatio = resizeData.startWidth / resizeData.startHeight;

                if (newWidth / newHeight > aspectRatio) {
                    newWidth = newHeight * aspectRatio;
                } else {
                    newHeight = newWidth / aspectRatio;
                }

                newWidth = Math.max(newWidth, 20);
                newHeight = Math.max(newHeight, 20);

                resizeData.img.style.width = newWidth + 'px';
                resizeData.img.style.height = newHeight + 'px';
            }

            function stopResize(e) {
                window.removeEventListener('mousemove', onResize);
                window.removeEventListener('mouseup', stopResize);
                resizeData = null;
            }

            function onDrag(e) {
                if (!dragData) return;
                let dx = e.clientX - dragData.startX;
                let dy = e.clientY - dragData.startY;
                let newLeft = dragData.origLeft + dx;
                let newTop = dragData.origTop + dy;

                dragData.wrapper.style.position = 'relative';
                dragData.wrapper.style.left = newLeft + 'px';
                dragData.wrapper.style.top = newTop + 'px';
            }

            function stopDrag(e) {
                window.removeEventListener('mousemove', onDrag);
                window.removeEventListener('mouseup', stopDrag);
                dragData = null;
            }

            function addQuestion() {
                const container = document.getElementById('questionsContainer');
                const questionCount = container.children.length + 1;

                const questionBlock = document.createElement('div');
                questionBlock.className = 'question-block';
                questionBlock.setAttribute('data-question-id', questionCount);

                const questionHeader = document.createElement('div');
                questionHeader.className = 'question-header';
                
                const questionLabel = document.createElement('label');
                questionLabel.innerHTML = `<i class="fas fa-question-circle"></i> Question ${questionCount}`;

                const typeSelect = document.createElement('select');
                typeSelect.className = 'question-type-select';
                typeSelect.name = `questions[${questionCount}][type]`;
                typeSelect.innerHTML = `
                    <option value="multiple_choice">Multiple Choice</option>
                    <option value="true_false">True/False</option>
                `;
                typeSelect.onchange = () => updateQuestionType(questionCount);

                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'delete-question-btn';
                deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                deleteBtn.onclick = () => {
                    if (confirm('Are you sure you want to delete this question?')) {
                        questionBlock.remove();
                        renumberQuestions();
                    }
                };

                questionHeader.appendChild(questionLabel);
                questionHeader.appendChild(typeSelect);
                questionHeader.appendChild(deleteBtn);

                const questionContent = document.createElement('div');
                questionContent.className = 'question-content';

                const qToolbar = document.createElement('div');
                qToolbar.className = 'toolbar';
                qToolbar.innerHTML = `
                    <button type="button" aria-label="Bold" onclick="editorCommand('bold')"><i class="fas fa-bold"></i></button>
                    <button type="button" aria-label="Italic" onclick="editorCommand('italic')"><i class="fas fa-italic"></i></button>
                    <button type="button" aria-label="Underline" onclick="editorCommand('underline')"><i class="fas fa-underline"></i></button>
                    <button type="button" aria-label="Unordered List" onclick="editorCommand('insertUnorderedList')"><i class="fas fa-list-ul"></i></button>
                    <button type="button" aria-label="Ordered List" onclick="editorCommand('insertOrderedList')"><i class="fas fa-list-ol"></i></button>
                `;

                const qEditable = document.createElement('div');
                qEditable.id = 'question_' + questionCount;
                qEditable.className = 'contenteditable';
                qEditable.contentEditable = 'true';
                qEditable.setAttribute('data-placeholder', 'Enter question text here...');
                qEditable.addEventListener('input', () => syncQuestionInput(questionCount));

                const qHidden = document.createElement('input');
                qHidden.type = 'hidden';
                qHidden.name = `questions[${questionCount}][text]`;
                qHidden.id = 'hidden_question_' + questionCount;

                const answerContainer = document.createElement('div');
                answerContainer.id = `answer_container_${questionCount}`;
                answerContainer.className = 'answer-container';

                questionContent.appendChild(qToolbar);
                questionContent.appendChild(qEditable);
                questionContent.appendChild(qHidden);
                questionContent.appendChild(answerContainer);

                questionBlock.appendChild(questionHeader);
                questionBlock.appendChild(questionContent);

                container.appendChild(questionBlock);
                
                createMultipleChoiceAnswers(questionCount);
            }

            function renumberQuestions() {
                const questions = document.querySelectorAll('.question-block');
                questions.forEach((question, index) => {
                    const number = index + 1;
                    question.setAttribute('data-question-id', number);
                    question.querySelector('label').innerHTML = `<i class="fas fa-question-circle"></i> Question ${number}`;
                    
                    question.querySelectorAll('[name*="questions["]').forEach(element => {
                        element.name = element.name.replace(/questions\[\d+\]/, `questions[${number}]`);
                    });
                });
            }

            function updateQuestionType(questionId) {
                const container = document.getElementById(`answer_container_${questionId}`);
                const type = document.querySelector(`[data-question-id="${questionId}"] select`).value;

                container.innerHTML = '';

                switch (type) {
                    case 'multiple_choice':
                        createMultipleChoiceAnswers(questionId);
                        break;
                }
            }

            function createMultipleChoiceAnswers(questionId) {
                const container = document.getElementById(`answer_container_${questionId}`);
                if (!container) {
                    console.error('Answer container not found');
                    return;
                }
                
                container.innerHTML = '';
                
                const choicesDiv = document.createElement('div');
                choicesDiv.id = `choices_${questionId}`;
                choicesDiv.className = 'choices';
                container.appendChild(choicesDiv);

                addChoice(questionId, 0);
                addChoice(questionId, 1);

                const addChoiceBtn = document.createElement('button');
                addChoiceBtn.type = 'button';
                addChoiceBtn.className = 'add-choice-btn';
                addChoiceBtn.innerHTML = '<i class="fas fa-plus"></i> Add Choice';
                addChoiceBtn.onclick = () => {
                    const count = choicesDiv.querySelectorAll('.choice-item').length;
                    addChoice(questionId, count);
                };

                container.appendChild(addChoiceBtn);
            }

            function syncQuestionInput(id) {
                const qEditable = document.getElementById('question_' + id);
                const qHidden = document.getElementById('hidden_question_' + id);
                if (qEditable && qHidden) {
                    qHidden.value = qEditable.innerHTML.trim();
                }
            }

            function addChoice(questionId, index) {
                const choicesDiv = document.getElementById(`choices_${questionId}`);
                if (!choicesDiv) {
                    console.error('Choices container not found');
                    return;
                }
                const choiceItem = document.createElement('div');
                choiceItem.className = 'choice-item';

                const radioInput = document.createElement('input');
                radioInput.type = 'radio';
                radioInput.name = `questions[${questionId}][correct]`;
                radioInput.value = index + 1;
                radioInput.required = true;
                radioInput.className = 'choice-radio';

                const choiceToolbar = document.createElement('div');
                choiceToolbar.className = 'toolbar';
                choiceToolbar.innerHTML = `
                    <button type="button" aria-label="Bold" onclick="editorCommand('bold')"><i class="fas fa-bold"></i></button>
                    <button type="button" aria-label="Italic" onclick="editorCommand('italic')"><i class="fas fa-italic"></i></button>
                    <button type="button" aria-label="Underline" onclick="editorCommand('underline')"><i class="fas fa-underline"></i></button>
                `;

                const choiceContent = document.createElement('div');
                choiceContent.id = `choice_${questionId}_${index + 1}`;
                choiceContent.className = 'contenteditable choice-content';
                choiceContent.contentEditable = 'true';
                choiceContent.setAttribute('data-placeholder', 'Enter choice text here...');
                choiceContent.addEventListener('input', () => syncChoiceInput(questionId, index + 1));

                const choiceHidden = document.createElement('input');
                choiceHidden.type = 'hidden';
                choiceHidden.name = `questions[${questionId}][choices][]`;
                choiceHidden.id = `hidden_choice_${questionId}_${index + 1}`;

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'remove-choice-btn';
                removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                removeBtn.onclick = () => {
                    if (choicesDiv.children.length > 2) {
                        choiceItem.remove();
                    } else {
                        alert('A multiple choice question must have at least 2 choices.');
                    }
                };

                choiceItem.appendChild(radioInput);
                choiceItem.appendChild(choiceToolbar);
                choiceItem.appendChild(choiceContent);
                choiceItem.appendChild(choiceHidden);
                choiceItem.appendChild(removeBtn);

                choicesDiv.appendChild(choiceItem);
            }

            function syncChoiceInput(questionId, choiceIndex) {
                const choiceEditor = document.getElementById(`choice_${questionId}_${choiceIndex}`);
                const choiceHidden = document.getElementById(`hidden_choice_${questionId}_${choiceIndex}`);
                if (choiceEditor && choiceHidden) {
                    choiceHidden.value = choiceEditor.innerHTML.trim();
                }
            }

            function validateForm() {
                const instructionsEditor = document.getElementById('quizInstructions');
                const instructionsHidden = document.getElementById('hidden_quiz_instructions');
                instructionsHidden.value = instructionsEditor.innerHTML.trim();

                const questions = document.querySelectorAll('.question-block');
                if (!questions.length) {
                    alert('Please add at least one question.');
                    return false;
                }

                for (let i = 0; i < questions.length; i++) {
                    const qDiv = questions[i].querySelector('.contenteditable');
                    const qHidden = questions[i].querySelector(`input[type=hidden][name^="questions"]`);
                    const qType = questions[i].querySelector('select').value;

                    if (!qDiv.textContent.trim()) {
                        alert('Please fill the question text for question ' + (i + 1));
                        qDiv.focus();
                        return false;
                    }
                    qHidden.value = qDiv.innerHTML.trim();

                    switch (qType) {
                        case 'multiple_choice':
                            const choices = questions[i].querySelectorAll('.choice-item');
                            if (choices.length < 2) {
                                alert('Each multiple choice question must have at least 2 choices.');
                                return false;
                            }
                            let hasCorrect = false;
                            for (let j = 0; j < choices.length; j++) {
                                const cRadio = choices[j].querySelector('input[type=radio]');
                                const cEditor = choices[j].querySelector('.contenteditable.choice-content');
                                const cHidden = choices[j].querySelector('input[type=hidden]');
                                if (!cEditor.textContent.trim()) {
                                    alert(`Please fill choice text for question ${i + 1}, choice ${j + 1}`);
                                    cEditor.focus();
                                    return false;
                                }
                                cHidden.value = cEditor.innerHTML.trim();
                                if (cRadio.checked) hasCorrect = true;
                            }
                            if (!hasCorrect) {
                                alert('Please select a correct answer for question ' + (i + 1));
                                return false;
                            }
                            break;
                    }
                }
                return true;
            }

            window.onload = () => {
                addQuestion();
            };

            // Update current time in navbar
            function updateTime() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', { 
                    hour12: true, 
                    hour: 'numeric', 
                    minute: '2-digit' 
                });
                const timeElement = document.getElementById('currentTime');
                if (timeElement) {
                    timeElement.textContent = timeString;
                }
            }

            // Update time immediately and then every minute
            updateTime();
            setInterval(updateTime, 60000);

            // Mobile menu toggle
            document.addEventListener('DOMContentLoaded', () => {
                const menuBtn = document.querySelector('.mobile-menu-btn');
                const sidebar = document.querySelector('.sidebar');
                
                if (menuBtn) {
                    menuBtn.addEventListener('click', () => {
                        sidebar.classList.toggle('active');
                    });
                }

                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', (e) => {
                    if (window.innerWidth <= 1024 && 
                        !sidebar.contains(e.target) && 
                        !menuBtn.contains(e.target) && 
                        sidebar.classList.contains('active')) {
                        sidebar.classList.remove('active');
                    }
                });
            });
        </script>
    </head>
    <body>
        <!-- Top Navbar -->
        <nav class="top-navbar">
            <a href="index.php" class="navbar-brand">
                <div class="navbar-logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <span class="navbar-title">Quizify Admin</span>
            </a>
            
            <div class="navbar-actions">
                <div class="navbar-time" id="currentTime"></div>
                <div class="navbar-user">
                    <div class="navbar-avatar">
                        <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                    </div>
                    <span class="navbar-username"><?= htmlspecialchars($_SESSION['username']) ?></span>
                </div>
            </div>
        </nav>

        <button class="mobile-menu-btn">
            <i class="fas fa-bars"></i>
        </button>

        <aside class="sidebar">
            <div class="sidebar-content">
                <nav>
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="index.php" class="nav-link">
                                <i class="fas fa-chart-line"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="create_quiz.php" class="nav-link active">
                                <i class="fas fa-plus-circle"></i>
                                Create Quiz
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="manage_quizzes.php" class="nav-link">
                                <i class="fas fa-tasks"></i>
                                Manage Quizzes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="manage_users.php" class="nav-link">
                                <i class="fas fa-users-cog"></i>
                                Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="rank.php" class="nav-link">
                                <i class="fas fa-trophy"></i>
                                Rankings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="admin_passkey.php" class="nav-link">
                                <i class="fas fa-key"></i>
                                Admin Passkey
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="profile_admin.php" class="nav-link">
                                <i class="fas fa-user-shield"></i>
                                Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="../logout.php" class="nav-link">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-plus-circle"></i>
                    Create New Quiz
                </h1>
            </div>

            <form class="quiz-form" id="quizForm" method="post" action="process_quiz.php" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="quizTitle">Quiz Title</label>
                    <input type="text" id="quizTitle" name="quiz_title" required placeholder="Enter quiz title...">
                </div>

                <div class="form-group">
                    <label for="quizInstructions">Quiz Instructions</label>
                    <div class="toolbar">
                        <button type="button" aria-label="Bold" onclick="editorCommand('bold')"><i class="fas fa-bold"></i></button>
                        <button type="button" aria-label="Italic" onclick="editorCommand('italic')"><i class="fas fa-italic"></i></button>
                        <button type="button" aria-label="Underline" onclick="editorCommand('underline')"><i class="fas fa-underline"></i></button>
                        <button type="button" aria-label="Unordered List" onclick="editorCommand('insertUnorderedList')"><i class="fas fa-list-ul"></i></button>
                        <button type="button" aria-label="Ordered List" onclick="editorCommand('insertOrderedList')"><i class="fas fa-list-ol"></i></button>
                    </div>
                    <div id="quizInstructions" class="contenteditable" contenteditable="true" 
                        data-placeholder="Enter quiz instructions here..."></div>
                    <input type="hidden" name="quiz_instructions" id="hidden_quiz_instructions">
                    <small class="helper-text">These instructions will be shown to users before they start the quiz.</small>
                </div>

                <div class="form-group">
                    <label for="quizTimer">Time Limit (minutes)</label>
                    <input type="number" id="quizTimer" name="time_limit" min="1" max="180" value="30" required>
                    <small class="helper-text">Set the time limit for this quiz (1-180 minutes)</small>
                </div>

                <div id="questionsContainer">
                    <!-- Questions will be added here dynamically -->
                </div>

                <div class="form-actions">
                    <button type="button" onclick="addQuestion()" class="add-question-btn">
                        <i class="fas fa-plus"></i> Add Question
                    </button>

                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i> Save Quiz
                    </button>
                </div>
            </form>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About Quizify</h3>
                    <p>Quizify is an interactdive quiz management platform designed to help educators create, manage, and analyze quiz performance with ease.</p>
                    <div class="footer-social">
                        <a href="https://www.facebook.com/jhovan.balbuena.16/" class="social-link"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://www.linkedin.com/in/jhovan-balbuena-230009368/" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                        <a href="https://www.instagram.com/jhovsbalbuena/" class="social-link"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Dashboard</a></li>
                        <li><a href="create_quiz.php">Create Quiz</a></li>
                        <li><a href="manage_quizzes.php">Manage Quizzes</a></li>
                        <li><a href="manage_users.php">Manage Users</a></li>
                        <li><a href="rank.php">Rankings</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Support</h3>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Documentation</a></li>
                        <li><a href="#">Contact Support</a></li>
                        <li><a href="#">System Status</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p><i class="fas fa-envelope"></i> madelyneway78@gmail.com</p>
                    <p> jhovanbalbuena27@gmail.com</p>
                    <p><i class="fas fa-phone"></i> +63 9633 316076</p>
                    <p><i class="fas fa-map-marker-alt"></i> Silago, Southern Leyte</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Quizify Admin Portal</span>
                </div>
                <p>&copy; 2024 Quizify. All rights reserved. Built with ❤️ for educators.</p>
            </div>
        </footer>
    </body>
    </html>
