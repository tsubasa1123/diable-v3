# 🎓 Student Guide - IDOR Lab

## Welcome!

This guide will walk you through exploiting an Insecure Direct Object Reference (IDOR) vulnerability step-by-step.

---

## 🎯 Your Mission

You are a regular student user, but you need to access the administrator's account to retrieve a secret FLAG. The application has a security flaw that allows you to do this.

---

## 📚 What is IDOR?

### Simple Explanation

Imagine a school where everyone has a locker with a number:
- **Your locker:** #1
- **Admin's locker:** #0
- **Other students:** #2, #3, #4...

Now imagine the school gives you a key that opens **any** locker if you just type in the number. That's IDOR!

The school checks:
- ✅ "Do you have a key?" (Authentication)

But doesn't check:
- ❌ "Should you open THIS locker?" (Authorization)

### Technical Explanation

**IDOR** happens when:
1. An application uses IDs to access objects (users, files, records)
2. These IDs are exposed to users (in URLs, APIs, forms)
3. The application doesn't verify if you should access that ID
4. You can access other users' data by changing the ID

---

## 🚀 Step-by-Step Walkthrough

### Phase 1: Understanding the Application

#### Step 1: Access the Lab

1. Open your web browser
2. Go to: `http://localhost:8082`
3. You should see the login page

#### Step 2: Login

1. Use these credentials:
   - **Username:** `student`
   - **Password:** `password123`
2. Click "Login"

#### Step 3: Observe Your Profile

After logging in, you'll be redirected to your profile page. Notice:

- **Your username:** student
- **Your role:** Student
- **URL in browser:** `http://localhost:8082/profile?user_id=1`

👁️ **Pay attention to that URL!** The `user_id=1` is important!

---

### Phase 2: Reconnaissance

#### Step 4: Explore the Application

Click on "All Users" in the navigation bar. You'll see:

- **Admin** (👑) - ID: 0
- **Student** (👤) - ID: 1 (that's you!)
- **Alice** (👩) - ID: 2
- **Bob** (👨) - ID: 3

💡 **Notice:** Each user has a visible ID number!

#### Step 5: Try Clicking on Another User

Click on "Alice" or "Bob". What happens?

- You can see their profile!
- The URL changes to their ID: `?user_id=2` or `?user_id=3`
- You're viewing someone else's data!

🤔 **Think:** If you can view Alice and Bob's profiles, can you view the admin's profile?

---

### Phase 3: The Exploit

#### Step 6: Access the Admin Profile

Now for the moment of truth:

1. Look at your browser's address bar
2. You'll see something like: `http://localhost:8082/profile?user_id=2`
3. **Manually change** the `2` to `0`
4. Press **Enter**

You should now see:
```
http://localhost:8082/profile?user_id=0
```

#### Step 7: Success!

You should now see:
- 👑 **ADMINISTRATOR ACCOUNT** banner
- Admin's profile information
- 🚩 **The FLAG!**

---

## 🎉 Congratulations!

You've successfully exploited an IDOR vulnerability!

### What You Did

1. **Discovered** that user IDs are in the URL
2. **Noticed** IDs are sequential and predictable (0, 1, 2, 3)
3. **Changed** the URL parameter from your ID (1) to admin's ID (0)
4. **Accessed** data you shouldn't have access to
5. **Retrieved** the FLAG

### The FLAG

You should see something like:
```
CTF{IDOR_VULN_ACCESS_CONTROL_BYPASS_2024}
```

---

## 🔍 Understanding What Went Wrong

### The Vulnerability

The application code looks like this:

```python
@app.route('/profile')
def profile():
    # Check if user is logged in
    if 'user_id' not in session:
        return redirect('/login')  # ✓ Authentication works
    
    # Get the requested user_id from URL
    requested_user_id = request.args.get('user_id')
    
    # ❌ NO CHECK if logged-in user should access this ID!
    user_data = USERS[requested_user_id]
    return render_template('profile.html', user=user_data)
```

### What's Missing?

The application should have:

```python
@app.route('/profile')
def profile():
    if 'user_id' not in session:
        return redirect('/login')
    
    requested_user_id = request.args.get('user_id')
    logged_in_user_id = session['user_id']
    
    # ✓ AUTHORIZATION CHECK
    if requested_user_id != logged_in_user_id:
        if not is_admin(logged_in_user_id):
            return "Unauthorized", 403  # Access denied!
    
    user_data = USERS[requested_user_id]
    return render_template('profile.html', user=user_data)
```

---

## 💻 Alternative Exploitation Methods

### Method 1: Using Browser Console

After logging in, open Developer Tools (F12) and run:

```javascript
// Access admin profile via API
fetch('/api/user/0')
    .then(r => r.json())
    .then(data => {
        console.log('Admin data:', data);
        console.log('FLAG:', data.flag);
    });
```

### Method 2: Using cURL

```bash
# First, login and save cookies
curl -c cookies.txt -X POST http://localhost:8082/login \
  -d "username=student&password=password123"

# Then access admin profile
curl -b cookies.txt http://localhost:8082/profile?user_id=0
```

### Method 3: Using Python Script

```bash
# Use the provided exploit script
python3 exploit.py
```

---

## 🎯 Bonus Challenges

### Challenge 1: Enumerate All Users

Can you write a script to find all valid user IDs?

**Hint:** Try IDs from -1 to 10

```javascript
// Try this in browser console
for(let id = -1; id <= 10; id++) {
    fetch(`/api/user/${id}`)
        .then(r => r.json())
        .then(data => {
            if(data.username) {
                console.log(`ID ${id}: ${data.username} (${data.role})`);
            }
        })
        .catch(() => {});
}
```

### Challenge 2: What Other Information Can You Access?

- Can you see other users' emails?
- Can you see their creation dates?
- What about their permissions?

### Challenge 3: How Would You Fix This?

If you were the developer, how would you prevent this attack?

**Think about:**
- Authorization checks
- Hiding user IDs
- Using random tokens
- Access control lists

---

## 📖 Key Concepts to Remember

### Authentication vs Authorization

- **Authentication:** "Who are you?"
  - Logging in with username/password
  - Proves your identity
  - The app knows you're "student"

- **Authorization:** "What are you allowed to do?"
  - Checking if you can access specific data
  - The app should check if you can view admin's profile
  - ❌ This lab fails at this!

### Object References

- **Direct Object Reference:** Using actual IDs (`user_id=1`)
  - Easy to understand and use
  - But vulnerable if not protected!

- **Indirect Object Reference:** Using random tokens
  - Harder to guess
  - More secure
  - Example: `profile_token=a7f3b2c9`

### Predictable IDs

In this lab, IDs are:
- **Sequential:** 0, 1, 2, 3, 4...
- **Predictable:** Easy to guess next ID
- **Exposed:** Visible in URLs

Real applications should:
- Use random UUIDs
- Not expose internal IDs
- Implement proper access controls

---

## 🛡️ Real-World Impact

### Famous IDOR Vulnerabilities

1. **Facebook Photo Breach (2018)**
   - Attackers could access private photos
   - Changed photo ID in URL
   - Millions of users affected

2. **Instagram Private Posts**
   - Could view private account posts
   - Changed post ID in API request

3. **Banking Applications**
   - Access other customers' statements
   - Changed account number in request

4. **Healthcare Systems**
   - View other patients' records
   - Changed patient ID parameter

### Why IDOR is Dangerous

- **Easy to exploit** - Just change a number in URL
- **Hard to detect** - Looks like normal usage
- **High impact** - Access to sensitive data
- **Common** - Found in many applications
- **Often missed** - Developers forget authorization checks

---

## 📝 Lab Report Template

Document your findings:

```markdown
# IDOR Lab Report

## Student Information
- Name: [Your Name]
- Date: [Date]

## Executive Summary
[Brief description of what you did]

## Vulnerability Details
- **Type:** Insecure Direct Object Reference (IDOR)
- **Location:** /profile endpoint
- **Severity:** Critical

## Exploitation Steps
1. [Step 1]
2. [Step 2]
3. [Step 3]

## Proof of Concept
- **Original URL:** http://localhost:8082/profile?user_id=1
- **Exploited URL:** http://localhost:8082/profile?user_id=0
- **FLAG Retrieved:** [Your FLAG]

## Impact Assessment
[What could an attacker do with this vulnerability?]

## Recommendations
1. Implement proper authorization checks
2. Use indirect object references
3. Add access control lists
4. Log and monitor access patterns

## What I Learned
[Your reflection on the exercise]
```

---

## ❓ Frequently Asked Questions

### Q: Is changing the URL really all it takes?

**A:** Yes! In vulnerable applications, it's often this simple. That's why IDOR is so dangerous.

### Q: Why doesn't the app stop me?

**A:** The app checks if you're logged in (authentication) but not if you should access that specific resource (authorization).

### Q: Would this work in real applications?

**A:** Unfortunately, yes. Many real applications have this vulnerability, which is why it's in the OWASP Top 10.

### Q: How can I test for IDOR in other apps?

**A:** Always with permission! Look for:
- IDs in URLs or APIs
- Sequential or predictable IDs
- Try accessing other users' resources
- Use tools like Burp Suite

### Q: What if I can't find the admin's ID?

**A:** In this lab, it's ID=0. In real testing:
- Try common IDs: 0, 1, -1, 999999
- Look for user enumeration
- Check API responses for clues

---

## ✅ Completion Checklist

Mark off as you complete each step:

- [ ] Logged into the application
- [ ] Viewed your own profile (ID=1)
- [ ] Explored the "All Users" page
- [ ] Identified the admin's ID (0)
- [ ] Changed URL to access admin profile
- [ ] Retrieved the FLAG
- [ ] Understood the vulnerability
- [ ] Know how to prevent IDOR
- [ ] Completed bonus challenges (optional)
- [ ] Wrote lab report (optional)

---

## 🚀 Next Steps

After completing this lab:

1. **Try the MFA Bypass Lab** - Learn about authentication attacks
2. **Study OWASP Top 10** - Learn other vulnerabilities
3. **Practice on Bug Bounty Platforms** - HackerOne, BugCrowd (legally!)
4. **Read Security Blogs** - Stay updated on latest techniques
5. **Build Secure Apps** - Apply what you learned

---

## 📚 Additional Resources

### Learn More About IDOR
- OWASP Testing Guide
- PortSwigger Web Security Academy
- HackerOne Reports & Write-ups

### Practice More
- DVWA (Damn Vulnerable Web App)
- WebGoat
- Hack The Box

### Stay Legal & Ethical
- Only test with permission
- Report vulnerabilities responsibly
- Follow bug bounty rules
- Respect privacy

---

**Congratulations on completing the IDOR Lab!** 🎉

You now understand one of the most common web vulnerabilities. Use this knowledge to build more secure applications!

---

**Questions?** Review the main README.md or examine the application code in `app.py`.

Happy Hacking! 🔓
