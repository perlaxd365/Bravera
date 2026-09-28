---
name: brevare-apple-ui
description: Design and implement modern, minimal, premium Apple-inspired UI for the Brevare ecommerce platform. Use when building or styling Laravel 13 / Livewire 3 / Vite / Tailwind CSS / Flux UI components, views, or pages with Blade syntax.
---

# Apple UI — Brevare

## Purpose

Design and implement modern, minimal and premium user interfaces
for the Brevare ecommerce platform.

The visual language should be inspired by Apple's Human Interface
Guidelines without copying proprietary Apple interfaces.

## Technology Stack

- Laravel 13
- Livewire 3.8.2
- Vite
- Tailwind CSS
- Flux UI

## Core Principles

- Clarity
- Deference
- Depth
- Consistency
- Simplicity
- Accessibility
- Responsive design
- Mobile-first design

## Visual Style

Use:

- Clean layouts
- Generous spacing
- Soft rounded corners
- Subtle shadows
- Clear typography hierarchy
- Minimal borders
- Smooth transitions
- Restrained animations
- Large product imagery
- Strong visual hierarchy
- High-quality empty states
- Elegant loading states

Avoid:

- Excessive gradients
- Excessive shadows
- Cluttered interfaces
- Excessive animations
- Bootstrap
- AdminLTE
- Visually inconsistent components

## Brevare Architecture

Business logic must remain inside:

app/Modules/{Module}

Livewire components:

resources/views/livewire

Reusable UI components:

resources/views/components/brevare

Do not move business logic into Blade components.

## Component Rules

Prefer reusable components such as:

- Button
- Input
- Select
- Modal
- Card
- Badge
- Product Card
- Price
- Rating
- Alert
- Dropdown
- Loading
- Empty State

Use Flux UI components when appropriate.

## Ecommerce UI

Product interfaces should support:

- Product image
- Product name
- Brand
- Price
- Discount
- Rating
- Availability
- Wishlist
- Add to cart
- Buy now

## Responsive Design

Every component must work correctly on:

- Mobile
- Tablet
- Desktop
- Large desktop

Mobile must not be treated as an afterthought.

## Accessibility

Interfaces should provide:

- Semantic HTML
- Keyboard navigation
- Visible focus states
- Appropriate labels
- Accessible dialogs
- Sufficient contrast
- Screen-reader friendly content

## Implementation Rules

Before creating a new component:

1. Check whether an existing Brevare component can be reused.
2. Reuse existing components whenever possible.
3. Do not duplicate UI logic.
4. Do not modify business logic for visual changes.
5. Preserve Livewire functionality.
6. Preserve existing routes and APIs.
7. Preserve existing module architecture.

## Design Tokens

Use a neutral premium palette:

Primary:
#111827

Background:
#F9FAFB

Surface:
#FFFFFF

Border:
#E5E7EB

Muted:
#6B7280

Success:
#16A34A

Warning:
#F59E0B

Danger:
#DC2626

Dark background:
#09090B

Dark surface:
#18181B

Dark border:
#27272A

## Animation

Animations should be subtle.

Prefer:

- opacity
- transform
- scale
- blur
- height transitions

Avoid excessive motion.

Typical duration:

150ms
200ms
300ms

## Output Requirements

When generating UI code:

1. Use Laravel Blade syntax.
2. Use Livewire when interactivity is required.
3. Use Tailwind CSS.
4. Use Flux UI when appropriate.
5. Keep components reusable.
6. Explain architectural changes briefly.
7. Never introduce React or Vue unless explicitly requested.