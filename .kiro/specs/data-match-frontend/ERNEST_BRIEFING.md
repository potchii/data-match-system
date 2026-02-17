# 🎯 Ernest's Task Briefing - Data Match System Frontend

**Date:** February 17, 2026  
**Project:** Data Match System  
**Your Role:** Frontend & UI Developer  
**Duration:** 5 weeks (Phases 1-5)  
**Tech Stack:** React 19, TypeScript, Tailwind CSS, Radix UI, Inertia.js

---

## 📌 Quick Overview

You're building the frontend for a **Data Match System** that allows users to:
1. Upload Excel/CSV files with records
2. See matching results with confidence scores
3. View batch history and statistics
4. Export and manage results

**Your Responsibility:** All UI/UX, forms, tables, modals, and user interactions.

---

## 🏗️ Project Structure

The project is already set up with:
- ✅ Laravel backend (Mason's responsibility)
- ✅ React + Inertia.js frontend framework
- ✅ Tailwind CSS for styling
- ✅ Radix UI components library
- ✅ TypeScript for type safety

**Your work location:** `resources/js/` directory

---

## 📋 What You Need to Build

### Phase 1: Dashboard & Upload (Week 1) - 15 hours
- Dashboard page with navigation
- Upload page with file validation
- Error handling and validation messages

### Phase 2: Results Display (Week 2) - 21 hours
- Match results table with sorting/filtering
- Batch summary widget with statistics
- Match details modal for viewing details

### Phase 3: Batch History (Week 3) - 11 hours
- Batch history page with search/filter
- Batch status indicators
- Delete batch functionality

### Phase 4: Advanced Features (Week 4) - 14 hours
- Export results to Excel
- Bulk actions on multiple records
- User settings and preferences

### Phase 5: Polish & Optimization (Week 5) - 20 hours
- Performance optimization
- Accessibility improvements
- Mobile responsiveness

---

## 🎨 Design System

### Colors
```
Primary: Blue (#3B82F6)
Success: Green (#10B981)
Warning: Amber (#F59E0B)
Danger: Red (#EF4444)

Status Badges:
- MATCHED: Green
- POSSIBLE DUPLICATE: Yellow
- NEW RECORD: Red
```

### Components Available
- Buttons, Dialogs, Dropdowns, Inputs, Labels, Checkboxes
- Separators, Avatars, Badges, Tooltips
- All from Radix UI (already installed)

### Styling
- Use Tailwind CSS classes (no custom CSS unless necessary)
- Follow existing component patterns
- Maintain consistency with current design

---

## 🔌 API Endpoints (Mason will provide)

```
POST   /api/upload                    - Upload file
GET    /api/batches/:id/results       - Get match results
GET    /api/batches                   - Get batch history
GET    /api/batches/:id               - Get batch details
GET    /api/batches/:id/export        - Export results
DELETE /api/batches/:id               - Delete batch
```

---

## 📁 File Structure You'll Create

```
resources/js/
├── pages/
│   ├── dashboard.tsx                 ← Dashboard page
│   ├── upload.tsx                    ← Upload page
│   ├── match-results.tsx             ← Results page
│   ├── batch-history.tsx             ← History page
│   └── settings/
│       ├── profile.tsx
│       ├── preferences.tsx
│       ├── notifications.tsx
│       └── security.tsx
├── components/
│   ├── batch-summary.tsx             ← Stats widget
│   ├── results-table.tsx             ← Results table
│   ├── status-badge.tsx              ← Status badge
│   ├── match-details-modal.tsx       ← Details modal
│   ├── file-upload-form.tsx          ← Upload form
│   ├── batch-list.tsx                ← Batch list
│   └── batch-status-badge.tsx        ← Status indicator
├── hooks/
│   ├── use-file-validation.ts        ← File validation
│   ├── use-batch-results.ts          ← Results fetching
│   └── use-batches.ts                ← Batches fetching
└── types/
    ├── batch.ts                      ← Batch type
    ├── match-result.ts               ← Result type
    └── user.ts                       ← User type
```

---

## ✅ Key Requirements

### Must Have (MVP)
- ✅ Dashboard with navigation
- ✅ Upload page with validation
- ✅ Match results table
- ✅ Batch summary widget
- ✅ Batch history page
- ✅ Error handling

### Should Have
- ✅ Match details modal
- ✅ Export functionality
- ✅ Status indicators
- ✅ Search and filter

### Nice to Have
- ✅ Bulk actions
- ✅ User settings
- ✅ Real-time updates
- ✅ Advanced analytics

---

## 🎯 Success Criteria

Your work is done when:
- ✅ All pages render without errors
- ✅ All forms validate correctly
- ✅ All API calls handled properly
- ✅ All error states display correctly
- ✅ Mobile responsive on all screen sizes
- ✅ Keyboard accessible
- ✅ Lighthouse score > 90
- ✅ Zero console errors
- ✅ All tests passing
- ✅ Code reviewed by Mason

---

## 🚀 Getting Started

### Step 1: Set Up Environment
```bash
# Install dependencies
npm install

# Start development server
npm run dev

# In another terminal, start Laravel
php artisan serve
```

### Step 2: Create First Component
```bash
# Create dashboard page
touch resources/js/pages/dashboard.tsx
```

### Step 3: Follow the Spec
- Read `requirements.md` for what to build
- Read `design.md` for how to design it
- Read `tasks.md` for step-by-step tasks
- Complete tasks in order

### Step 4: Version Control
```bash
# Create feature branch
git checkout -b feature/dashboard-layout

# Make changes, commit, push
git add .
git commit -m "feat: add dashboard layout"
git push origin feature/dashboard-layout

# Create pull request for Mason to review
```

---

## 📊 Data Models

### Batch
```typescript
{
  id: string
  fileName: string
  uploadedBy: string
  uploadedAt: Date
  status: 'processing' | 'completed' | 'failed'
  totalRecords: number
  matchedCount: number
  newCount: number
  duplicateCount: number
}
```

### MatchResult
```typescript
{
  id: string
  batchId: string
  uploadedRecord: {
    firstName: string
    lastName: string
    birthday?: Date
    gender?: string
  }
  matchedRecord?: {
    id: string
    firstName: string
    lastName: string
    birthday?: Date
  }
  status: 'matched' | 'possible_duplicate' | 'new'
  confidenceScore: number (0-100)
}
```

---

## 🔐 Important Notes

### Security
- Never hardcode API keys or secrets
- Validate all user input
- Sanitize file uploads
- Use CSRF tokens (Inertia handles this)

### Performance
- Lazy load pages with React.lazy()
- Memoize expensive components
- Optimize images
- Implement virtual scrolling for large tables

### Accessibility
- All interactive elements keyboard accessible
- ARIA labels on buttons and icons
- Color contrast meets WCAG AA
- Focus indicators visible

### Testing
- Test on desktop, tablet, and mobile
- Test with keyboard navigation
- Test with screen reader
- Test error scenarios

---

## 📞 Communication

### Daily Standup
- Time: 9:00 AM
- Duration: 15 minutes
- Report: What you did, what you'll do, blockers

### Code Review
- Create pull request when task complete
- Wait for Mason's review
- Address feedback
- Merge when approved

### Blockers
- Report immediately to Mason
- Don't wait for standup
- Provide context and what you've tried

### Questions
- Ask Mason for clarification
- Check the spec documents first
- Look at existing code patterns

---

## 🎓 Learning Resources

### Existing Code
- Look at `resources/js/components/` for component patterns
- Look at `resources/js/pages/` for page patterns
- Look at `resources/js/hooks/` for hook patterns

### Documentation
- Tailwind CSS: https://tailwindcss.com
- Radix UI: https://www.radix-ui.com
- React: https://react.dev
- Inertia.js: https://inertiajs.com
- TypeScript: https://www.typescriptlang.org

### Tools
- React DevTools (browser extension)
- Tailwind CSS IntelliSense (VS Code)
- ESLint (code quality)
- Prettier (code formatting)

---

## 📈 Progress Tracking

### Week 1 (Phase 1)
- [ ] Dashboard layout
- [ ] Upload page
- [ ] Form validation
- **Target:** 15 hours

### Week 2 (Phase 2)
- [ ] Results table
- [ ] Batch summary
- [ ] Details modal
- **Target:** 21 hours

### Week 3 (Phase 3)
- [ ] Batch history
- [ ] Status indicators
- **Target:** 11 hours

### Week 4 (Phase 4)
- [ ] Export functionality
- [ ] Bulk actions
- [ ] User settings
- **Target:** 14 hours

### Week 5 (Phase 5)
- [ ] Performance optimization
- [ ] Accessibility improvements
- [ ] Mobile responsiveness
- **Target:** 20 hours

---

## 🎯 First Task

**Start here:** Phase 1, Task 1.1 - Dashboard Layout & Navigation

1. Create `resources/js/pages/dashboard.tsx`
2. Use existing `app-shell.tsx` component
3. Add navigation links to sidebar
4. Add dashboard route to `routes/web.php`
5. Test locally
6. Create pull request
7. Wait for review

**Estimated time:** 4 hours

---

## 💡 Pro Tips

1. **Start simple:** Build basic structure first, add features later
2. **Test early:** Test each component as you build it
3. **Ask questions:** Better to ask than to guess
4. **Follow patterns:** Look at existing code for patterns
5. **Commit often:** Small commits are easier to review
6. **Read errors:** Error messages tell you what's wrong
7. **Use DevTools:** React DevTools helps debug
8. **Mobile first:** Design for mobile, then scale up

---

## 🎉 You've Got This!

You have everything you need to build an amazing frontend. The spec is detailed, the tech stack is modern, and Mason is there to help.

**Remember:**
- Follow the spec
- Complete tasks in order
- Test thoroughly
- Ask for help when needed
- Celebrate small wins

Let's build something great! 🚀

---

## 📚 Quick Reference

### Commands
```bash
npm run dev          # Start dev server
npm run build        # Build for production
npm run lint         # Check code quality
npm run format       # Format code
npm run types        # Check TypeScript
```

### File Locations
- Pages: `resources/js/pages/`
- Components: `resources/js/components/`
- Hooks: `resources/js/hooks/`
- Types: `resources/js/types/`
- Routes: `routes/web.php`

### Key Files
- `resources/js/app.tsx` - App entry point
- `resources/js/layouts/app-layout.tsx` - Main layout
- `tailwind.config.js` - Tailwind configuration
- `tsconfig.json` - TypeScript configuration

---

**Good luck, Ernest! You've got this! 💪**
