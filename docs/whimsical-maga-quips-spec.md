# Whimsical Trump Quips - Project Specification

## 1. Project Overview
- **Project Name:** Whimsical Trump Quips
- **Purpose:** A site that shows different outrageous quotes, comments, actions or general things that either Trump, his administration, or republicans have done. Eventual purpose to be an archive that people can reference to show just how bad the Trump administration is.
- **Target Audience:** Political oriented people. A reference site, but also a humorous site that can be shared with people.
- **Core Concept:** The main idea - rotating quotes with uplifting, whimsical backgrounds, humorous/serious content mix. A whimsical style is core to the app's identity.

## 2. Tech Stack
- **Frontend:** Vue 3 + TypeScript
- **Styling:** Tailwind CSS
- **Component Library:** Shadcn-vue
- **Backend:** Laravel
- **Bridge:** Inertia.js (monorepo)
- **Database:** PostgreSQL
- **Hosting:** Railway or Fly.io or some other hosting that offers free or cheap hosting
- **Version Control:** Git/GitHub

## 3. Phase 1 - MVP (Minimum Viable Product)

### 3.1 Core Features
- Quote display with cycling/rotation
- Background image cycling
- **Admin authentication (login/logout)**
- **Admin dashboard with management tools**
- Quote management (admin only - CRUD operations)
- Background management (admin only - CRUD operations)
- Tag and category assignment (admin only, backend preparation)

### 3.2 Data Requirements
- Quotes table
- Backgrounds table
- Tags table
- Categories table
- Quote-tag relationships (pivot table)
- Quote-category relationships
- **Users table**
- **Roles table**
- **User role assignment (users have role_id foreign key)**

### 3.3 User Experience

#### Public Users
- View rotating quotes (tags/categories not visible to users yet)
- See backgrounds cycle
- No user accounts for public

#### Admin Users
- **Login via dedicated admin login page**
- **Access admin dashboard**
- Create, edit, delete quotes
- Assign tags and categories to quotes
- Upload and manage background images
- Create and manage tags/categories

### 3.4 Technical Notes
- Database schema includes tags/categories from the start
- Admin panel allows tagging, but frontend doesn't display or filter by them yet
- Sets foundation for Phase 2 without requiring data migration
- **Authentication: Laravel Breeze or Jetstream (simple, built-in)**
- **Admin routes protected by middleware (auth + role:admin check)**
- **Separate admin layout/interface from public site**

## 4. Phase 2 - Enhanced Discovery

### 4.1 Features
- **Display tags and categories on quote cards**
- Search functionality
- **Filtering by tags**
- **Filtering by categories**
- Browse/explore pages for tags and categories

### 4.2 Data Requirements
- (Already exists from Phase 1)
- Tags table ✓
- Categories table ✓
- Quote-tag relationships ✓
- Quote-category relationships ✓
- **Possibly add: tag descriptions, category descriptions**

### 4.3 User Experience
- Browse quotes by tag
- Filter by category
- Search quotes by keyword
- See tags displayed on each quote
- Click tags/categories to see related quotes
- Explore all available tags and categories

## 5. Phase 3 - User Interaction

### 5.1 Features
- User accounts and authentication
- User-submitted quotes
- Comments/evidence on quotes
- User interaction tracking

### 5.2 Data Requirements
- Users table (expanded from Phase 1 admin-only)
- User-submitted quotes (with approval workflow?)
- Comments/evidence tables
- User activity tracking

### 5.3 User Experience
- Create account / login
- Submit own quotes
- Add evidence/context to quotes
- Track personal usage history

## 6. Phase 4+ - Future Enhancements (Optional)

### 6.1 Potential Features
- Voting/rating system
- Social sharing
- Quote of the day
- Email notifications
- API for third parties
- Mobile app considerations

### 6.2 Ideas for Later
- [Any other ideas you want to capture but not commit to]

## 7. Data Model Considerations

### 7.1 Core Entities
- Quotes
- Backgrounds/Images
- Tags
- Categories
- Users (Phase 1: admin only; Phase 3: public users)
- Roles

### 7.2 Key Relationships
- Roles → Users (one-to-many)
- Users → Quotes (one-to-many: creates)
- Quotes → Sources (one-to-many: has many)
- Quotes ↔ Backgrounds (separate pools, randomly paired - no direct relationship)
- Quotes ↔ Tags (many-to-many via pivot table)
- Quotes ↔ Categories (many-to-many via pivot table)

### 7.3 Important Fields to Consider
- Timestamps (created_at, updated_at)
- Soft deletes (deleted_at)
- Status fields (published, draft, pending)
- Metadata fields (views, featured, etc.)

## 8. Technical Considerations

### 8.1 Performance
- Image optimization strategy
- Caching approach
- Database indexing priorities

### 8.2 Security

#### Authentication Approach
- **Laravel Breeze** for simple authentication (recommended for Phase 1)
- **Role-based access control** via roles table:
  - Phase 1: Admin role only
  - Phase 3+: User, Moderator, Contributor roles as needed
- Session-based authentication (Inertia works great with this)

#### Authorization
- Policies/gates for role-based actions
- All admin routes behind `auth` and `role:admin` middleware
- No public user registration in Phase 1 (admin creates accounts manually if needed)
- User input sanitization
- XSS protection

#### Roles Structure (Phase 1)
```
Roles:
- Admin: Full access to all admin features

Future roles (Phase 3+):
- Moderator: Approve quotes, manage tags/categories
- User: Submit quotes, view own submissions
```

#### Routes Structure
```
Public routes:
- / (homepage with rotating quotes)

Admin routes (protected):
- /admin/login (if separate from main login)
- /admin/dashboard
- /admin/quotes (CRUD)
- /admin/backgrounds (CRUD)
- /admin/tags (CRUD)
- /admin/categories (CRUD)
```

### 8.3 Asset Management

#### Phase 1: Local Storage
- Store background images in Laravel's `storage/app/public/backgrounds/`
- Use Laravel's storage facade for uploads
- Symlink storage to public directory
- Admin can upload via admin panel
- Maximum file size limit (e.g., 5MB per image)
- Accept formats: JPG, PNG, WebP

#### Image Sources
- **Free stock photos:** Unsplash, Pexels, Pixabay
- **License:** Ensure commercial use allowed
- **Optimization:** Compress images before upload (TinyPNG, ImageOptim)
- **Recommended size:** 1920x1080 or similar for full-screen backgrounds

#### Phase 2+: Cloud Storage Migration (Optional)
- Migrate to Cloudflare R2 or AWS S3 for better performance
- Implement CDN for faster image delivery
- Keep database structure same (just update URLs)

#### Database Storage
- `backgrounds` table stores `file_path` (Phase 1) or `url` (Phase 2+)
- Store original filename, size, dimensions for reference

## 9. Admin Panel Requirements

### Phase 1 Admin Features
- **Login/logout functionality**
- **Dashboard overview (basic stats if desired)**
- Manage quotes (CRUD)
  - Create quote with text, source, date, context
  - Assign tags (multi-select)
  - Assign category (single or multi-select)
  - Associate background image(s)
  - Set status (published/draft)
- Manage backgrounds (CRUD)
  - Upload images
  - Add alt text/descriptions
  - Preview
- Manage tags (CRUD)
  - Create, edit, delete tags
- Manage categories (CRUD)
  - Create, edit, delete categories

### Admin UI Considerations
- Use Shadcn-vue components for consistent admin interface
- Forms with proper validation
- Success/error notifications
- Confirm dialogs for destructive actions (delete)

### Phase 3+ Admin Features
- Approve user-submitted content
- View analytics/stats
- User management

## 10. Development Milestones

### Milestone 1: MVP Launch
- Working quote rotation
- Admin can manage content (quotes, backgrounds, tags, categories)
- Admin authentication working
- Deployed and accessible

### Milestone 2: Enhanced Discovery
- Full tagging/categorization visible on frontend
- Search working
- Filtering functional
- Improved UX

### Milestone 3: Community Features
- User accounts live
- User submissions working
- Moderation tools functional

## 11. Open Questions / Decisions Needed
- How long should quote rotation intervals be? (30 seconds? User configurable?)
- Should there be multiple quote display modes? (random, chronological, featured)
- Should categories be single-select or multi-select per quote? (Currently: multi-select)
- Admin account creation: manual via tinker/seeder or simple registration page (protected)?
- Background cycling: synchronized with quote cycling or independent timers?

## 12. Database Seeders & Initial Data

### Roles Seeder
```php
// database/seeders/RoleSeeder.php
Role::create([
    'name' => 'admin',
    'description' => 'Full access to all features and settings'
]);

Role::create([
    'name' => 'moderator', 
    'description' => 'Can approve quotes, manage tags/categories'
]);

Role::create([
    'name' => 'user',
    'description' => 'Can submit quotes and view own submissions'
]);
```

### Admin User Seeder (Phase 1)
```php
// database/seeders/AdminUserSeeder.php
$adminRole = Role::where('name', 'admin')->first();

User::create([
    'name' => 'Admin',
    'email' => 'your@email.com',
    'password' => bcrypt('your-secure-password'),
    'role_id' => $adminRole->id,
    'email_verified_at' => now(),
]);
```

### Helper Methods for User Model
```php
// app/Models/User.php
public function isAdmin(): bool
{
    return $this->role->name === 'admin';
}

public function hasRole(string $role): bool
{
    return $this->role->name === $role;
}

public function isModerator(): bool
{
    return $this->role->name === 'moderator';
}
```

## 13. Database Indexes (For Performance)

### Recommended Indexes
```php
// Quotes table
INDEX on status
INDEX on is_featured
INDEX on user_id
INDEX on occurred_at
INDEX on created_at

// Tags/Categories
INDEX on slug (for URL lookups)
INDEX on name

// Users
INDEX on role_id
INDEX on email (unique)

// Pivot tables
UNIQUE INDEX on (quote_id, tag_id)
UNIQUE INDEX on (quote_id, category_id)

// Sources
INDEX on quote_id
```

## 14. Design & Styling Notes

### Whimsical Style Direction
The whimsical aesthetic is core to the app's identity and should influence:

- **Typography:** Playful but readable fonts (consider: Quicksand, Poppins, Nunito for headings)
- **Colors:** Light, uplifting color palette (pastels, bright accents)
- **Spacing:** Generous whitespace, airy layouts
- **Borders:** Rounded corners throughout
- **Animations:** Smooth, delightful transitions (quotes fading in/out, backgrounds crossfading)
- **Components:** Soft shadows, gradient accents
- **Contrast:** The juxtaposition of serious political content against whimsical, uplifting backgrounds

### Component Styling with Tailwind + Shadcn-vue
- Use Shadcn-vue components as base
- Customize with Tailwind to achieve whimsical aesthetic
- Consistent animation timing (ease-in-out transitions)
- Hover states should feel responsive and playful

### Background Image Strategy
- Uplifting imagery: nature scenes, colorful abstracts, positive vibes
- Creates ironic contrast with potentially serious quote content
- Makes heavy political content more digestible and shareable

---

**Implementation Note:** This specification document should be referenced throughout development. For Phase 1, focus on the MVP features. Database schema includes all tables from the start to avoid migrations between phases.
