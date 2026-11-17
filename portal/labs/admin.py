from django.contrib import admin

from .models import Lab


@admin.register(Lab)
class LabAdmin(admin.ModelAdmin):
    list_display = ['name', 'slug', 'url', 'is_active', 'sort_order']
    list_editable = ['is_active', 'sort_order']
    prepopulated_fields = {'slug': ('name',)}
